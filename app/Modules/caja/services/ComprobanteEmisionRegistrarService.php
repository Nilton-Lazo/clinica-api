<?php

namespace App\Modules\caja\services;

use App\Core\support\CuentaOrigen;
use App\Core\support\EstadoFacturacionServicio;
use App\Models\User;
use App\Modules\admision\models\CitaAtencion;
use App\Modules\admision\models\CitaAtencionServicio;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\PreFacturacionHospitalariaRegistro;
use App\Modules\admision\models\RegistroEmergenciaServicio;
use App\Modules\caja\models\CajaEmisionComprobante;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComprobanteEmisionRegistrarService
{
    public function __construct(
        private CajaAperturaService $cajaAperturaService,
    ) {}

    /**
     * @param  array{nro_cuenta: string, servicio_linea_ids: array<int, int>, numero_operacion: ?string, snapshot: array}  $data
     */
    public function registrar(User $actor, array $data): CajaEmisionComprobante
    {
        $apertura = $this->cajaAperturaService->requireAperturaNormalAbierta($actor);

        $nroCuenta = (string) $data['nro_cuenta'];
        $cuenta = Cuenta::query()->where('nro_cuenta', $nroCuenta)->firstOrFail();

        $ids = array_values(array_unique(array_map('intval', $data['servicio_linea_ids'] ?? [])));
        $numeroOp = isset($data['numero_operacion']) ? trim((string) $data['numero_operacion']) : '';
        $numeroOp = $numeroOp === '' ? null : $numeroOp;

        return DB::transaction(function () use ($actor, $apertura, $nroCuenta, $cuenta, $ids, $numeroOp, $data) {
            if ($ids !== []) {
                $origen = (string) $cuenta->origen;
                match ($origen) {
                    CuentaOrigen::CITA_ATENCION->value => $this->marcarServiciosCitaAtencion($cuenta, $ids),
                    CuentaOrigen::REGISTRO_EMERGENCIA->value => $this->marcarServiciosEmergencia($cuenta, $ids),
                    CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value => $this->marcarServiciosHospital($cuenta, $ids),
                    default => throw ValidationException::withMessages([
                        'nro_cuenta' => ['Origen de cuenta no soportado para registrar en caja.'],
                    ]),
                };
            }

            return CajaEmisionComprobante::create([
                'user_id' => $actor->id,
                'caja_apertura_id' => $apertura->id,
                'nro_cuenta' => $nroCuenta,
                'cuenta_origen' => (string) $cuenta->origen,
                'numero_operacion' => $numeroOp,
                'snapshot' => $data['snapshot'],
            ]);
        });
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function marcarServiciosCitaAtencion(Cuenta $cuenta, array $ids): void
    {
        $atencion = CitaAtencion::query()->findOrFail((int) $cuenta->origen_id);
        $n = CitaAtencionServicio::query()
            ->where('cita_atencion_id', $atencion->id)
            ->whereIn('id', $ids)
            ->where('estado_facturacion', EstadoFacturacionServicio::PENDIENTE->value)
            ->count();

        if ($n !== count($ids)) {
            throw ValidationException::withMessages([
                'servicio_linea_ids' => ['Las líneas no corresponden a la cuenta o ya fueron facturadas.'],
            ]);
        }

        CitaAtencionServicio::query()
            ->where('cita_atencion_id', $atencion->id)
            ->whereIn('id', $ids)
            ->update(['estado_facturacion' => EstadoFacturacionServicio::FACTURADO->value]);
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function marcarServiciosEmergencia(Cuenta $cuenta, array $ids): void
    {
        $registroId = (int) $cuenta->origen_id;
        $n = RegistroEmergenciaServicio::query()
            ->where('registro_emergencia_id', $registroId)
            ->whereIn('id', $ids)
            ->where('estado_facturacion', EstadoFacturacionServicio::PENDIENTE->value)
            ->count();

        if ($n !== count($ids)) {
            throw ValidationException::withMessages([
                'servicio_linea_ids' => ['Las líneas no corresponden a la cuenta o ya fueron facturadas.'],
            ]);
        }

        RegistroEmergenciaServicio::query()
            ->where('registro_emergencia_id', $registroId)
            ->whereIn('id', $ids)
            ->update(['estado_facturacion' => EstadoFacturacionServicio::FACTURADO->value]);
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function marcarServiciosHospital(Cuenta $cuenta, array $ids): void
    {
        $reg = PreFacturacionHospitalariaRegistro::query()->findOrFail((int) $cuenta->origen_id);
        if (trim((string) ($reg->nro_cuenta ?? '')) !== trim((string) $cuenta->nro_cuenta)) {
            throw ValidationException::withMessages([
                'nro_cuenta' => ['La cuenta no coincide con el registro hospitalario.'],
            ]);
        }

        $payload = is_array($reg->payload) ? $reg->payload : [];
        $lineas = isset($payload['lineas']) && is_array($payload['lineas']) ? $payload['lineas'] : [];
        $idSet = array_fill_keys($ids, true);
        $matched = 0;

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
                    'servicio_linea_ids' => ['Una o más líneas ya no están pendientes de facturación.'],
                ]);
            }
            $lineas[$i]['estado_facturacion'] = EstadoFacturacionServicio::FACTURADO->value;
            $matched++;
        }

        if ($matched !== count($ids)) {
            throw ValidationException::withMessages([
                'servicio_linea_ids' => ['Las líneas no corresponden a la cuenta o ya fueron facturadas.'],
            ]);
        }

        $payload['lineas'] = $lineas;
        $reg->payload = $payload;
        $reg->save();
    }
}
