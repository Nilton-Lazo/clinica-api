<?php

namespace App\Modules\caja\services;

use App\Core\support\CuentaOrigen;
use App\Core\support\EstadoFacturacionServicio;
use App\Core\support\ComprobanteEmisionCuentaOrigenFilter;
use App\Core\support\ComprobanteEmisionOrigen;
use App\Core\realtime\RealtimeBroadcaster;
use App\Models\User;
use App\Modules\admision\models\CajaBancoTarjeta;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\admision\models\CajaMedioPago;
use App\Modules\admision\models\CajaTipoDocumento;
use App\Modules\admision\models\CitaAtencion;
use App\Modules\admision\models\CitaAtencionServicio;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\CajaNumeracionComprobante;
use App\Modules\admision\models\Paquete;
use App\Modules\admision\models\ParametroSistema;
use App\Modules\admision\models\PreFacturacionHospitalariaRegistro;
use App\Modules\admision\models\RegistroEmergencia;
use App\Modules\admision\models\RegistroEmergenciaServicio;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaServicio;
use App\Modules\caja\models\CajaEmisionComprobante;
use App\Modules\caja\models\CajaEmisionComprobantePago;
use App\Modules\caja\models\CajaNumeracionComprobanteCorrelativo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ComprobanteEmisionRegistrarService
{
    public function __construct(
        private CajaAperturaService $cajaAperturaService,
        private RealtimeBroadcaster $realtime,
    ) {}

    public function registrar(User $actor, array $data): CajaEmisionComprobante
    {
        $apertura = $this->cajaAperturaService->requireAperturaNormalAbierta($actor);

        $nroCuenta = (string) $data['nro_cuenta'];
        $emisionOrigen = trim((string) ($data['emision_origen'] ?? ''));
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
        $originesPermitidos = ComprobanteEmisionCuentaOrigenFilter::cuentaOriginesFor($emisionOrigen);
        if (! is_array($originesPermitidos)) {
            throw ValidationException::withMessages([
                'emision_origen' => ['El origen de emisión seleccionado no está soportado para registrar comprobantes.'],
            ]);
        }
        if ($originesPermitidos === [] || ! in_array((string) $cuenta->origen, $originesPermitidos, true)) {
            throw ValidationException::withMessages([
                'nro_cuenta' => ['La cuenta seleccionada no pertenece al origen de emisión elegido. Recarga y selecciona una cuenta válida.'],
            ]);
        }

        $ids = array_values(array_unique(array_map('intval', $data['servicio_linea_ids'] ?? [])));
        $numeroOp = isset($data['numero_operacion']) ? trim((string) $data['numero_operacion']) : '';
        $numeroOp = $numeroOp === '' ? null : $numeroOp;
        $fechaVencimiento = isset($data['fecha_vencimiento']) ? trim((string) $data['fecha_vencimiento']) : '';
        $fechaVencimiento = $fechaVencimiento === '' ? null : $fechaVencimiento;
        $formaPagoId = (int) $data['forma_pago_id'];
        $medioPagoId = (int) $data['medio_pago_id'];
        $bancoTarjetaId = isset($data['banco_tarjeta_id']) ? (int) $data['banco_tarjeta_id'] : null;
        $bancoTarjetaId = $bancoTarjetaId !== null && $bancoTarjetaId > 0 ? $bancoTarjetaId : null;
        $adelantoCfg = $this->adelantoConfig();
        $adelanto = $this->normalizarAdelanto($data, $adelantoCfg['servicio_codigo']);
        $esOrigenHospital = $emisionOrigen === ComprobanteEmisionOrigen::HOSPITALIZACION->value;

        return DB::transaction(function () use ($actor, $apertura, $nroCuenta, $cuenta, $ids, $numeroOp, $fechaVencimiento, $formaPagoId, $medioPagoId, $bancoTarjetaId, $data, $adelantoCfg, $adelanto, $esOrigenHospital) {
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
            $tipoCodigo = trim((string) (CajaTipoDocumento::query()
                ->whereKey((int) $numeracion->tipo_documento_id)
                ->value('codigo') ?? ''));
            $esReciboCaja = $tipoCodigo !== '' && strtoupper($tipoCodigo) === strtoupper($adelantoCfg['tipo_documento_codigo_recibo']);
            if ($adelanto['enabled']) {
                if (! $esOrigenHospital || (string) $cuenta->origen !== CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value) {
                    throw ValidationException::withMessages([
                        'adelanto.enabled' => ['El adelanto solo se permite cuando el origen del comprobante es Hospitalización.'],
                    ]);
                }
                if (! $esReciboCaja) {
                    throw ValidationException::withMessages([
                        'adelanto.enabled' => ['El adelanto solo se permite cuando el tipo de comprobante es Recibo caja.'],
                    ]);
                }
                if ($adelanto['monto_con_igv'] <= 0) {
                    throw ValidationException::withMessages([
                        'adelanto.monto_con_igv' => ['Ingresa un monto de adelanto mayor a cero.'],
                    ]);
                }
                $this->assertServicioAdelantoDisponible($cuenta, $adelanto['servicio_codigo']);
            }
            $this->assertCuentaPermiteNuevaEmision($nroCuenta, $adelanto['enabled']);

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

            $formaPago = CajaFormaPago::query()
                ->activos()
                ->whereKey($formaPagoId)
                ->first();
            if (! $formaPago) {
                throw ValidationException::withMessages([
                    'forma_pago_id' => ['La forma de pago seleccionada no está activa. Actualiza la pantalla y vuelve a intentar.'],
                ]);
            }

            $medioPago = CajaMedioPago::query()
                ->activos()
                ->whereKey($medioPagoId)
                ->whereHas('formasPago', fn ($q) => $q->whereKey($formaPagoId))
                ->first();
            if (! $medioPago) {
                throw ValidationException::withMessages([
                    'medio_pago_id' => ['El medio de pago no está activo o no pertenece a la forma de pago seleccionada.'],
                ]);
            }

            $bancosCompatiblesQuery = CajaBancoTarjeta::query()
                ->activos()
                ->whereHas('formasPago', fn ($q) => $q->whereKey($formaPagoId))
                ->whereHas('mediosPago', fn ($q) => $q->whereKey($medioPagoId));
            $hayBancosCompatibles = $bancosCompatiblesQuery->exists();

            $bancoTarjeta = null;
            if ($bancoTarjetaId !== null) {
                $bancoTarjeta = (clone $bancosCompatiblesQuery)
                    ->whereKey($bancoTarjetaId)
                    ->first();
                if (! $bancoTarjeta) {
                    throw ValidationException::withMessages([
                        'banco_tarjeta_id' => ['El banco o tarjeta no está activo o no pertenece a la combinación de forma y medio de pago.'],
                    ]);
                }
            } elseif ($hayBancosCompatibles) {
                throw ValidationException::withMessages([
                    'banco_tarjeta_id' => ['Selecciona el banco o tarjeta para el medio de pago elegido.'],
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
            $facturoServicios = false;
            if ($adelanto['enabled']) {
                $totalPaciente = $adelanto['monto_con_igv'];
                $totalLineas = 1;
            } else {
                $origen = (string) $cuenta->origen;
                [$totalPaciente, $totalLineas] = match ($origen) {
                    CuentaOrigen::CITA_ATENCION->value => $this->marcarServiciosCitaAtencion($cuenta, $ids),
                    CuentaOrigen::REGISTRO_EMERGENCIA->value => $this->marcarServiciosEmergencia($cuenta, $ids),
                    CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value => $this->marcarServiciosHospital($cuenta, $ids, false),
                    default => throw ValidationException::withMessages([
                        'nro_cuenta' => ['El origen de la cuenta seleccionada no está soportado para registrar emisión en caja.'],
                    ]),
                };
                $facturoServicios = $totalLineas > 0;
            }

            if ($facturoServicios) {
                $cuenta->estado = 'CANCELADO';
                $cuenta->save();
            }

            $row = CajaEmisionComprobante::create([
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

            $montoPago = round($totalPaciente, 2);
            $pagoRow = [
                'emision_comprobante_id' => (int) $row->id,
                'forma_pago_id' => (int) $formaPago->id,
                'medio_pago_id' => (int) $medioPago->id,
                'banco_tarjeta_id' => $bancoTarjeta !== null ? (int) $bancoTarjeta->id : null,
                'numero_operacion' => $numeroOp,
                'monto' => $montoPago,
            ];
            if (trim((string) ($formaPago->codigo ?? '')) === '002') {
                $pagoRow['fecha_vencimiento'] = $fechaVencimiento;
            }
            CajaEmisionComprobantePago::create($pagoRow);
            if ($adelanto['enabled']) {
                $snapshot['adelanto'] = [
                    'enabled' => true,
                    'servicio_codigo' => $adelanto['servicio_codigo'],
                    'monto_con_igv' => number_format($adelanto['monto_con_igv'], 2, '.', ''),
                    'etiqueta' => 'GARANTIA',
                ];
                $row->snapshot = $snapshot;
                $row->save();
            }

            $this->realtime->entityChanged(
                module: 'caja',
                entity: 'emision_comprobante',
                action: 'issued',
                id: (int) $row->id,
                scope: (string) $nroCuenta,
                metadata: [
                    'nro_cuenta' => $nroCuenta,
                    'caja_apertura_id' => (int) $apertura->id,
                    'numeracion_id' => (int) $numeracion->id,
                    'numero_emitido' => $numeroEmitido,
                    'forma_pago_id' => (int) $formaPago->id,
                    'medio_pago_id' => (int) $medioPago->id,
                    'monto_pagado' => number_format($montoPago, 2, '.', ''),
                    'monto_source' => (string) ($snapshot['monto_source'] ?? 'default'),
                    'adelanto_garantia' => $adelanto['enabled'],
                ],
                actorId: (int) $actor->id,
            );

            if ((string) $cuenta->origen === CuentaOrigen::REGISTRO_EMERGENCIA->value) {
                $this->realtime->entityChanged(
                    module: 'emergencia',
                    entity: 'atencion_emergencia',
                    action: 'updated',
                    id: (int) $cuenta->origen_id,
                    scope: (string) $nroCuenta,
                    metadata: ['nro_cuenta' => $nroCuenta, 'facturado' => $facturoServicios, 'adelanto_garantia' => $adelanto['enabled']],
                    actorId: (int) $actor->id,
                );
            }

            if ((string) $cuenta->origen === CuentaOrigen::CITA_ATENCION->value) {
                $this->realtime->entityChanged(
                    module: 'admision',
                    entity: 'cita_atencion',
                    action: 'updated',
                    id: (int) $cuenta->origen_id,
                    scope: (string) $nroCuenta,
                    metadata: ['nro_cuenta' => $nroCuenta, 'facturado' => $facturoServicios, 'adelanto_garantia' => $adelanto['enabled']],
                    actorId: (int) $actor->id,
                );
            }

            if ((string) $cuenta->origen === CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value) {
                $this->realtime->entityChanged(
                    module: 'admision',
                    entity: 'prefacturacion_hospitalaria',
                    action: 'updated',
                    id: (int) $cuenta->origen_id,
                    scope: (string) $nroCuenta,
                    metadata: ['nro_cuenta' => $nroCuenta, 'facturado' => $facturoServicios, 'adelanto_garantia' => $adelanto['enabled']],
                    actorId: (int) $actor->id,
                );
            }

            return $row;
        });
    }

    private function marcarServiciosCitaAtencion(Cuenta $cuenta, array $ids): array
    {
        if ($ids === []) {
            throw ValidationException::withMessages([
                'servicio_linea_ids' => ['La cuenta ambulatoria no tiene servicios pendientes seleccionados para facturar.'],
            ]);
        }
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
            $categoriaCodigo = strtoupper(trim((string) ($row->tarifaServicio?->categoria?->codigo ?? '')));
            $total += $this->calcularMontoPacienteLinea(
                qty: $qty,
                precioConIgv: $precio,
                copVar: (float) ($row->cop_var ?? 0),
                copFijo: (float) ($row->cop_fijo ?? 0),
                categoriaCodigo: $categoriaCodigo,
                tarifaEsPrecioDirecto: $tarifaEsPrecioDirecto,
            );
        }

        CitaAtencionServicio::query()
            ->where('cita_atencion_id', $atencion->id)
            ->whereIn('id', $ids)
            ->update(['estado_facturacion' => EstadoFacturacionServicio::FACTURADO->value]);
        return [round($total, 2), $n];
    }

    private function marcarServiciosEmergencia(Cuenta $cuenta, array $ids): array
    {
        if ($ids === []) {
            throw ValidationException::withMessages([
                'servicio_linea_ids' => ['La cuenta de emergencia no tiene servicios pendientes seleccionados para facturar.'],
            ]);
        }
        $registroId = (int) $cuenta->origen_id;
        $registro = RegistroEmergencia::query()
            ->with(['tarifa:id,es_precio_directo'])
            ->find($registroId);
        if (! $registro) {
            throw ValidationException::withMessages([
                'nro_cuenta' => ['No se encontró el registro de emergencia asociado a la cuenta seleccionada.'],
            ]);
        }
        $tarifaEsPrecioDirecto = (bool) ($registro->tarifa?->es_precio_directo ?? false);
        if (! $tarifaEsPrecioDirecto && $cuenta->tarifa_id !== null) {
            $tarifaEsPrecioDirecto = (bool) (Tarifa::query()
                ->whereKey((int) $cuenta->tarifa_id)
                ->value('es_precio_directo') ?? false);
        }
        $rows = RegistroEmergenciaServicio::query()
            ->where('registro_emergencia_id', $registroId)
            ->whereIn('id', $ids)
            ->where('estado_facturacion', EstadoFacturacionServicio::PENDIENTE->value)
            ->with(['tarifaServicio.categoria:id,codigo'])
            ->get(['id', 'tarifa_servicio_id', 'cop_var', 'cop_fijo', 'cantidad', 'precio_con_igv']);
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
            $categoriaCodigo = strtoupper(trim((string) ($row->tarifaServicio?->categoria?->codigo ?? '')));
            $total += $this->calcularMontoPacienteLinea(
                qty: $qty,
                precioConIgv: $precio,
                copVar: (float) ($row->cop_var ?? 0),
                copFijo: (float) ($row->cop_fijo ?? 0),
                categoriaCodigo: $categoriaCodigo,
                tarifaEsPrecioDirecto: $tarifaEsPrecioDirecto,
            );
        }

        RegistroEmergenciaServicio::query()
            ->where('registro_emergencia_id', $registroId)
            ->whereIn('id', $ids)
            ->update(['estado_facturacion' => EstadoFacturacionServicio::FACTURADO->value]);
        return [round($total, 2), $n];
    }

    private function marcarServiciosHospital(Cuenta $cuenta, array $ids, bool $permitirSinServiciosPorAdelanto = false): array
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
        $paquete = isset($payload['presupuestoPaquete']) && is_array($payload['presupuestoPaquete'])
            ? $payload['presupuestoPaquete']
            : (isset($payload['presupuesto_paquete']) && is_array($payload['presupuesto_paquete'])
                ? $payload['presupuesto_paquete']
                : null);
        $tarifaEsPrecioDirecto = false;
        if ($cuenta->tarifa_id !== null) {
            $tarifaEsPrecioDirecto = (bool) (Tarifa::query()
                ->whereKey((int) $cuenta->tarifa_id)
                ->value('es_precio_directo') ?? false);
        }
        if ($ids === []) {
            if (! is_array($paquete)) {
                if ($permitirSinServiciosPorAdelanto) {
                    return [0.0, 0];
                }
                throw ValidationException::withMessages([
                    'servicio_linea_ids' => ['La cuenta hospitalaria no tiene servicios ni paquete pendientes para facturar.'],
                ]);
            }
            $estadoPaquete = strtoupper(trim((string) ($paquete['estado_facturacion'] ?? EstadoFacturacionServicio::PENDIENTE->value)));
            if ($estadoPaquete !== EstadoFacturacionServicio::PENDIENTE->value) {
                throw ValidationException::withMessages([
                    'servicio_linea_ids' => ['El paquete de la cuenta hospitalaria ya no está pendiente de facturación. Recarga la cuenta.'],
                ]);
            }
            [$montoPaquete, $montoSource] = $this->resolverMontoPaqueteHospital($paquete, $cuenta);
            if ($montoPaquete <= 0) {
                throw ValidationException::withMessages([
                    'servicio_linea_ids' => ['El paquete de la cuenta hospitalaria no tiene un monto válido para facturar.'],
                ]);
            }
            $payload['monto_source'] = $montoSource;
            if ($montoSource !== 'payload.precio_con_igv') {
                Log::info('caja.emision.hospitalizacion.monto_paquete_resuelto_por_fallback', [
                    'nro_cuenta' => (string) $cuenta->nro_cuenta,
                    'cuenta_origen_id' => (int) $cuenta->origen_id,
                    'tarifa_id' => $cuenta->tarifa_id !== null ? (int) $cuenta->tarifa_id : null,
                    'paquete_id' => isset($paquete['id']) ? (int) $paquete['id'] : null,
                    'monto_source' => $montoSource,
                    'monto_resuelto' => number_format($montoPaquete, 2, '.', ''),
                ]);
            }
            $paquete['estado_facturacion'] = EstadoFacturacionServicio::FACTURADO->value;
            $payload['presupuestoPaquete'] = $paquete;
            $reg->payload = $payload;
            $reg->save();
            $serviciosPaquete = isset($paquete['servicios']) && is_array($paquete['servicios']) ? $paquete['servicios'] : [];
            $lineasPaquete = count($serviciosPaquete);

            return [round($montoPaquete, 2), max(1, $lineasPaquete)];
        }

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
            $categoriaCodigo = strtoupper(trim((string) ($line['categoria_codigo'] ?? '')));
            $copVar = isset($line['cop_var']) ? (float) $line['cop_var'] : 0.0;
            $copFijo = isset($line['cop_fijo']) ? (float) $line['cop_fijo'] : 0.0;
            $total += $this->calcularMontoPacienteLinea(
                qty: $qty,
                precioConIgv: $precio,
                copVar: $copVar,
                copFijo: $copFijo,
                categoriaCodigo: $categoriaCodigo,
                tarifaEsPrecioDirecto: $tarifaEsPrecioDirecto,
            );
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

    private function calcularMontoPacienteLinea(
        float $qty,
        float $precioConIgv,
        float $copVar,
        float $copFijo,
        string $categoriaCodigo,
        bool $tarifaEsPrecioDirecto,
    ): float {
        $qty = max(0.0, $qty);
        $precioConIgv = max(0.0, $precioConIgv);
        if ($tarifaEsPrecioDirecto) {
            return $qty * $precioConIgv;
        }
        if (strtoupper(trim($categoriaCodigo)) === '50') {
            $copFijo = max(0.0, $copFijo);
            return $copFijo > 0 ? ($copFijo * $qty) : 0.0;
        }
        $copVar = max(0.0, min(100.0, $copVar));

        return ($qty * $precioConIgv) * (1.0 - ($copVar / 100.0));
    }

    private function resolverMontoPaqueteHospital(array $paquete, Cuenta $cuenta): array
    {
        $precioConIgv = isset($paquete['precio_con_igv']) ? (float) $paquete['precio_con_igv'] : 0.0;
        if ($precioConIgv > 0) {
            return [round($precioConIgv, 2), 'payload.precio_con_igv'];
        }

        $precioSinIgv = isset($paquete['precio_sin_igv']) ? (float) $paquete['precio_sin_igv'] : 0.0;
        if ($precioSinIgv > 0) {
            $igvPct = ParametroSistema::getIgvPorcentaje();

            return [round($precioSinIgv * (1.0 + ($igvPct / 100.0)), 2), 'payload.precio_sin_igv'];
        }

        $paqueteId = isset($paquete['id']) ? (int) $paquete['id'] : 0;
        if ($paqueteId > 0) {
            $precioPaqueteSinIgv = (float) (Paquete::query()->whereKey($paqueteId)->value('precio_sin_igv') ?? 0);
            if ($precioPaqueteSinIgv > 0) {
                $igvPct = ParametroSistema::getIgvPorcentaje();

                return [round($precioPaqueteSinIgv * (1.0 + ($igvPct / 100.0)), 2), 'db.paquetes.precio_sin_igv'];
            }
        }

        return [0.0, 'not_found'];
    }

    private function adelantoConfig(): array
    {
        return [
            'tipo_documento_codigo_recibo' => trim((string) config('caja.emision_recibo_caja_tipo_documento_codigo', '005')),
            'servicio_codigo' => trim((string) config('caja.emision_adelanto_garantia_servicio_codigo', '00.18.03')),
        ];
    }

    private function normalizarAdelanto(array $data, string $codigoPorDefecto): array
    {
        $raw = isset($data['adelanto']) && is_array($data['adelanto']) ? $data['adelanto'] : [];
        $enabled = (bool) ($raw['enabled'] ?? false);
        $codigo = trim((string) ($raw['servicio_codigo'] ?? $codigoPorDefecto));
        if ($codigo === '') {
            $codigo = $codigoPorDefecto;
        }
        $monto = isset($raw['monto_con_igv']) ? (float) $raw['monto_con_igv'] : 0.0;
        $monto = round(max(0, $monto), 2);

        return [
            'enabled' => $enabled,
            'servicio_codigo' => $codigo,
            'monto_con_igv' => $monto,
        ];
    }

    private function assertServicioAdelantoDisponible(Cuenta $cuenta, string $codigo): void
    {
        if ($cuenta->tarifa_id === null) {
            throw ValidationException::withMessages([
                'adelanto.servicio_codigo' => ['La cuenta no tiene una tarifa asociada para validar el servicio de adelanto.'],
            ]);
        }
        $exists = TarifaServicio::query()
            ->where('tarifa_id', (int) $cuenta->tarifa_id)
            ->whereRaw('UPPER(TRIM(codigo)) = ?', [strtoupper($codigo)])
            ->exists();
        if (! $exists) {
            throw ValidationException::withMessages([
                'adelanto.servicio_codigo' => ['El servicio configurado para adelanto no existe en la tarifa de la cuenta seleccionada.'],
            ]);
        }
    }

    private function assertCuentaPermiteNuevaEmision(string $nroCuenta, bool $esAdelanto): void
    {
        $emisiones = CajaEmisionComprobante::query()
            ->where('nro_cuenta', $nroCuenta)
            ->get(['snapshot']);
        if ($emisiones->isEmpty()) {
            return;
        }
        if ($esAdelanto) {
            return;
        }
        foreach ($emisiones as $emision) {
            $snap = is_array($emision->snapshot) ? $emision->snapshot : [];
            $adelanto = isset($snap['adelanto']) && is_array($snap['adelanto']) ? $snap['adelanto'] : [];
            $esGarantia = (bool) ($adelanto['enabled'] ?? false);
            if (! $esGarantia) {
                throw ValidationException::withMessages([
                    'nro_cuenta' => ['La cuenta ya tiene una emisión registrada y no puede volver a emitirse.'],
                ]);
            }
        }
    }
}
