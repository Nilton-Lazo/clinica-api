<?php

namespace App\Modules\caja\services;

use App\Core\support\CuentaOrigen;
use App\Core\support\EstadoFacturacionServicio;
use App\Models\User;
use App\Modules\admision\models\CitaAtencion;
use App\Modules\admision\models\CitaAtencionServicio;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\CajaNumeracionComprobante;
use App\Modules\admision\models\PreFacturacionHospitalariaRegistro;
use App\Modules\admision\models\RegistroEmergenciaServicio;
use App\Modules\caja\models\CajaEmisionComprobante;
use App\Modules\caja\models\CajaNumeracionComprobanteCorrelativo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComprobanteEmisionRegistrarService
{
    public function __construct(
        private CajaAperturaService $cajaAperturaService,
    ) {}

    public function registrar(User $actor, array $data): CajaEmisionComprobante
    {
        $apertura = $this->cajaAperturaService->requireAperturaNormalAbierta($actor);

        $nroCuenta = (string) $data['nro_cuenta'];
        $cuenta = Cuenta::query()->where('nro_cuenta', $nroCuenta)->first();
        if (! $cuenta) {
            throw ValidationException::withMessages([
                'nro_cuenta' => ['No existe una cuenta con el número ingresado. Verifica la cuenta y vuelve a cargarla.'],
            ]);
        }
        if (strtoupper(trim((string) $cuenta->estado)) === 'CANCELADO') {
            throw ValidationException::withMessages([
                'nro_cuenta' => ['La cuenta ya está cancelada o facturada y no admite una nueva emisión.'],
            ]);
        }

        $ids = array_values(array_unique(array_map('intval', $data['servicio_linea_ids'] ?? [])));
        if ($ids === []) {
            throw ValidationException::withMessages([
                'servicio_linea_ids' => ['La cuenta no tiene servicios pendientes seleccionados para facturar.'],
            ]);
        }
        $numeroOp = isset($data['numero_operacion']) ? trim((string) $data['numero_operacion']) : '';
        $numeroOp = $numeroOp === '' ? null : $numeroOp;
        $fechaVencimiento = isset($data['fecha_vencimiento']) ? trim((string) $data['fecha_vencimiento']) : '';
        $fechaVencimiento = $fechaVencimiento === '' ? null : $fechaVencimiento;

        return DB::transaction(function () use ($actor, $apertura, $nroCuenta, $cuenta, $ids, $numeroOp, $fechaVencimiento, $data) {
            $yaEmitida = CajaEmisionComprobante::query()
                ->where('nro_cuenta', $nroCuenta)
                ->exists();
            if ($yaEmitida) {
                throw ValidationException::withMessages([
                    'nro_cuenta' => ['La cuenta ya tiene una emisión registrada y no puede volver a emitirse.'],
                ]);
            }

            $numeracion = CajaNumeracionComprobante::query()
                ->whereKey((int) $data['numeracion_id'])
                ->where('estado', 'ACTIVO')
                ->lockForUpdate()
                ->first();
            if (! $numeracion) {
                throw ValidationException::withMessages([
                    'numeracion_id' => ['La serie seleccionada no existe o ya no está activa para emitir comprobantes.'],
                ]);
            }

            DB::table('caja_numeracion_comprobante_correlativos')->insertOrIgnore([
                'numeracion_comprobante_id' => (int) $numeracion->id,
                'next_numero' => max(1, (int) $numeracion->numero),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $correlativo = CajaNumeracionComprobanteCorrelativo::query()
                ->where('numeracion_comprobante_id', (int) $numeracion->id)
                ->lockForUpdate()
                ->first();
            if (! $correlativo) {
                throw ValidationException::withMessages([
                    'numeracion_id' => ['No se pudo reservar el correlativo de la serie seleccionada. Intenta nuevamente.'],
                ]);
            }

            $ultimoEmitido = (int) (CajaEmisionComprobante::query()
                ->where('numeracion_comprobante_id', (int) $numeracion->id)
                ->max('numero_emitido') ?? 0);
            $siguiente = max(1, (int) $correlativo->next_numero, $ultimoEmitido + 1);
            $numeroEmitido = $siguiente;
            $correlativo->next_numero = $numeroEmitido + 1;
            $correlativo->save();

            $snapshot = is_array($data['snapshot']) ? $data['snapshot'] : [];
            $form = isset($snapshot['form']) && is_array($snapshot['form']) ? $snapshot['form'] : [];
            $form['correlativo'] = str_pad((string) $numeroEmitido, 7, '0', STR_PAD_LEFT);
            $snapshot['form'] = $form;

            $totalPaciente = 0.0;
            $totalLineas = 0;
            if ($ids !== []) {
                $origen = (string) $cuenta->origen;
                [$totalPaciente, $totalLineas] = match ($origen) {
                    CuentaOrigen::CITA_ATENCION->value => $this->marcarServiciosCitaAtencion($cuenta, $ids),
                    CuentaOrigen::REGISTRO_EMERGENCIA->value => $this->marcarServiciosEmergencia($cuenta, $ids),
                    CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value => $this->marcarServiciosHospital($cuenta, $ids),
                    default => throw ValidationException::withMessages([
                        'nro_cuenta' => ['El origen de la cuenta seleccionada no está soportado para registrar emisión en caja.'],
                    ]),
                };
            }

            $cuenta->estado = 'CANCELADO';
            $cuenta->save();

            return CajaEmisionComprobante::create([
                'user_id' => $actor->id,
                'caja_apertura_id' => $apertura->id,
                'nro_cuenta' => $nroCuenta,
                'cuenta_origen' => (string) $cuenta->origen,
                'numeracion_comprobante_id' => (int) $numeracion->id,
                'serie' => (string) $numeracion->serie,
                'numero_emitido' => $numeroEmitido,
                'numero_operacion' => $numeroOp,
                'fecha_vencimiento' => $fechaVencimiento,
                'total_paciente' => round($totalPaciente, 2),
                'total_lineas' => $totalLineas,
                'snapshot' => $snapshot,
            ]);
        });
    }

    private function marcarServiciosCitaAtencion(Cuenta $cuenta, array $ids): array
    {
        $atencion = CitaAtencion::query()
            ->with(['tarifa:id,es_precio_directo'])
            ->find((int) $cuenta->origen_id);
        if (! $atencion) {
            throw ValidationException::withMessages([
                'nro_cuenta' => ['No se encontró la atención de cita asociada a la cuenta seleccionada.'],
            ]);
        }
        $rows = CitaAtencionServicio::query()
            ->where('cita_atencion_id', $atencion->id)
            ->whereIn('id', $ids)
            ->where('estado_facturacion', EstadoFacturacionServicio::PENDIENTE->value)
            ->with(['tarifaServicio.categoria:id,codigo'])
            ->get(['id', 'tarifa_servicio_id', 'cop_var', 'cop_fijo', 'cantidad', 'precio_con_igv']);
        $n = $rows->count();

        if ($n !== count($ids)) {
            throw ValidationException::withMessages([
                'servicio_linea_ids' => ['Uno o más servicios de atención de cita no pertenecen a la cuenta o ya fueron facturados. Recarga la cuenta.'],
            ]);
        }
        $total = 0.0;
        $tarifaEsPrecioDirecto = (bool) ($atencion->tarifa?->es_precio_directo ?? false);
        foreach ($rows as $row) {
            $qty = max(0.0, (float) ($row->cantidad ?? 0));
            $precio = max(0.0, (float) ($row->precio_con_igv ?? 0));
            if ($tarifaEsPrecioDirecto) {
                $total += $qty * $precio;
                continue;
            }
            $categoriaCodigo = strtoupper(trim((string) ($row->tarifaServicio?->categoria?->codigo ?? '')));
            if ($categoriaCodigo === '50') {
                $copFijo = max(0.0, (float) ($row->cop_fijo ?? 0));
                $total += $copFijo > 0 ? ($copFijo * $qty) : 0.0;
                continue;
            }
            $copVar = max(0.0, min(100.0, (float) ($row->cop_var ?? 0)));
            $total += ($qty * $precio) * (1.0 - ($copVar / 100.0));
        }

        CitaAtencionServicio::query()
            ->where('cita_atencion_id', $atencion->id)
            ->whereIn('id', $ids)
            ->update(['estado_facturacion' => EstadoFacturacionServicio::FACTURADO->value]);
        return [round($total, 2), $n];
    }

    private function marcarServiciosEmergencia(Cuenta $cuenta, array $ids): array
    {
        $registroId = (int) $cuenta->origen_id;
        $rows = RegistroEmergenciaServicio::query()
            ->where('registro_emergencia_id', $registroId)
            ->whereIn('id', $ids)
            ->where('estado_facturacion', EstadoFacturacionServicio::PENDIENTE->value)
            ->get(['id', 'cantidad', 'precio_con_igv']);
        $n = $rows->count();

        if ($n !== count($ids)) {
            throw ValidationException::withMessages([
                'servicio_linea_ids' => ['Uno o más servicios de emergencia no pertenecen a la cuenta o ya fueron facturados. Recarga la cuenta.'],
            ]);
        }
        $total = 0.0;
        foreach ($rows as $row) {
            $qty = max(0.0, (float) ($row->cantidad ?? 0));
            $precio = max(0.0, (float) ($row->precio_con_igv ?? 0));
            $total += $qty * $precio;
        }

        RegistroEmergenciaServicio::query()
            ->where('registro_emergencia_id', $registroId)
            ->whereIn('id', $ids)
            ->update(['estado_facturacion' => EstadoFacturacionServicio::FACTURADO->value]);
        return [round($total, 2), $n];
    }

    private function marcarServiciosHospital(Cuenta $cuenta, array $ids): array
    {
        $reg = PreFacturacionHospitalariaRegistro::query()->find((int) $cuenta->origen_id);
        if (! $reg) {
            throw ValidationException::withMessages([
                'nro_cuenta' => ['No se encontró la pre-facturación hospitalaria asociada a la cuenta seleccionada.'],
            ]);
        }
        if (trim((string) ($reg->nro_cuenta ?? '')) !== trim((string) $cuenta->nro_cuenta)) {
            throw ValidationException::withMessages([
                'nro_cuenta' => ['La cuenta seleccionada no coincide con el registro hospitalario asociado.'],
            ]);
        }

        $payload = is_array($reg->payload) ? $reg->payload : [];
        $lineas = isset($payload['lineas']) && is_array($payload['lineas']) ? $payload['lineas'] : [];
        $idSet = array_fill_keys($ids, true);
        $matched = 0;
        $total = 0.0;

        foreach ($lineas as $i => $line) {
            if (! is_array($line)) {
                continue;
            }
            $lid = isset($line['id']) ? (int) $line['id'] : 0;
            if ($lid <= 0 || ! isset($idSet[$lid])) {
                continue;
            }
            $est = isset($line['estado_facturacion']) ? (string) $line['estado_facturacion'] : EstadoFacturacionServicio::PENDIENTE->value;
            if ($est !== EstadoFacturacionServicio::PENDIENTE->value) {
                throw ValidationException::withMessages([
                    'servicio_linea_ids' => ['Uno o más servicios hospitalarios ya no están pendientes de facturación. Recarga la cuenta.'],
                ]);
            }
            $qty = isset($line['cantidad']) ? max(0.0, (float) $line['cantidad']) : 0.0;
            $precio = isset($line['precio_con_igv']) ? max(0.0, (float) $line['precio_con_igv']) : 0.0;
            $total += $qty * $precio;
            $lineas[$i]['estado_facturacion'] = EstadoFacturacionServicio::FACTURADO->value;
            $matched++;
        }

        if ($matched !== count($ids)) {
            throw ValidationException::withMessages([
                'servicio_linea_ids' => ['Uno o más servicios hospitalarios no pertenecen a la cuenta o ya fueron facturados. Recarga la cuenta.'],
            ]);
        }

        $payload['lineas'] = $lineas;
        $reg->payload = $payload;
        $reg->save();
        return [round($total, 2), $matched];
    }
}
