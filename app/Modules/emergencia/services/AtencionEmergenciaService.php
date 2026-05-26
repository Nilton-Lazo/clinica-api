<?php

namespace App\Modules\emergencia\services;

use App\Core\audit\AuditService;
use App\Core\NroCuentaService;
use App\Core\realtime\RealtimeBroadcaster;
use App\Core\support\EstadoFacturacionServicio;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\RegistroEmergenciaServicio;
use App\Modules\admision\models\RegistroEmergencia;
use App\Modules\admision\models\Paciente;
use App\Core\support\RecordStatus;
use App\Modules\admision\services\citas\CuentaSyncService;
use App\Modules\caja\support\EmisionComprobanteFacturacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtencionEmergenciaService
{
    public function __construct(
        private AuditService $audit,
        private NroCuentaService $nroCuentaService,
        private CuentaSyncService $cuentaSyncService,
        private RealtimeBroadcaster $realtime,
    ) {}

    public function datosParaAtencion(int $registroId): array
    {
        $registro = RegistroEmergencia::query()
            ->with([
                'servicios.tarifaServicio.categoria',
                'servicios.medico',
                'servicios.user'
            ])
            ->findOrFail($registroId);
        $cuenta = $this->findCuenta($registro);
        $cuentaBloqueada = $this->isCuentaBloqueada($cuenta, $registro->numero_cuenta);

        $paciente = Paciente::query()
            ->where('numero_documento', $registro->numero_hc)
            ->orWhere('nr', $registro->numero_hc)
            ->first();

        $serviciosPayload = [];
        if ($registro->servicios) {
            foreach ($registro->servicios as $s) {
                $ts = $s->tarifaServicio;
                $med = $s->medico;
                $user = $s->user;
                
                $userNombreCompleto = null;
                if ($user) {
                    $userNombreCompleto = $user->name && trim((string)$user->name) !== ''
                        ? trim((string)$user->name)
                        : trim(($user->apellido_paterno ?? '') . ' ' . ($user->apellido_materno ?? '') . ' ' . ($user->nombres ?? ''));
                    if ($userNombreCompleto === '') {
                        $userNombreCompleto = (string)$user->username;
                    }
                }
                
                $categoriaCodigo = $ts && $ts->relationLoaded('categoria') && $ts->categoria ? (string)$ts->categoria->codigo : null;
                
                $serviciosPayload[] = [
                    'id' => (int)$s->id,
                    'tarifa_servicio_id' => (int)$s->tarifa_servicio_id,
                    'servicio_codigo' => $ts ? (string)$ts->codigo : null,
                    'servicio_descripcion' => $ts ? (string)$ts->descripcion : null,
                    'categoria_codigo' => $categoriaCodigo,
                    'desea_liberar_precio' => $ts ? (bool)$ts->desea_liberar_precio : false,
                    'medico_id' => (int)$s->medico_id,
                    'medico_codigo' => $med ? (string)$med->codigo : null,
                    'medico_nombre' => $med ? trim($med->apellido_paterno . ' ' . $med->apellido_materno . ' ' . $med->nombres) : null,
                    'user_id' => $s->user_id ? (int)$s->user_id : null,
                    'user_username' => $user ? (string)$user->username : null,
                    'user_nombre' => $userNombreCompleto,
                    'cop_var' => (float)$s->cop_var,
                    'cop_fijo' => (float)$s->cop_fijo,
                    'descuento_pct' => (float)$s->descuento_pct,
                    'aumento_pct' => (float)$s->aumento_pct,
                    'cantidad' => (float)$s->cantidad,
                    'precio_sin_igv' => (float)$s->precio_sin_igv,
                    'precio_con_igv' => (float)$s->precio_con_igv,
                    'estado_facturacion' => $s->estado_facturacion && in_array((string)$s->estado_facturacion, EstadoFacturacionServicio::values(), true)
                    ? (string)$s->estado_facturacion
                    : EstadoFacturacionServicio::PENDIENTE->value,
                ];
            }
        }

        $horaStr = $registro->hora ? (is_string($registro->hora) ? substr($registro->hora, 0, 5) : null) : null;
        $horaAsistenciaStr = $registro->hora_asistencia ? (is_string($registro->hora_asistencia) ? substr($registro->hora_asistencia, 0, 5) : null) : null;

        return [
            'registro' => [
                'id' => (int)$registro->id,
                'orden' => $registro->orden,
                'hora' => $horaStr,
                'hora_asistencia' => $horaAsistenciaStr,
                'numero_hc' => $registro->numero_hc,
                'apellidos_nombres' => $registro->apellidos_nombres,
                'sexo' => $registro->sexo,
                'tipo_cliente' => $registro->tipo_cliente,
                'fecha' => $registro->fecha ? $registro->fecha->format('Y-m-d') : null,
                'cuenta' => $registro->cuenta,
                'numero_cuenta' => $registro->numero_cuenta,
                'medico_emergencia' => $registro->medico_emergencia,
                'medico_especialista' => $registro->medico_especialista,
                'topico' => $registro->topico,
                'estado' => $registro->estado,
                'paciente_plan_id' => $registro->paciente_plan_id ? (int)$registro->paciente_plan_id : null,
                'tarifa_id' => $registro->tarifa_id ? (int)$registro->tarifa_id : null,
                'parentesco_seguro' => $registro->parentesco_seguro ? (string)$registro->parentesco_seguro : null,
                'titular_nombre' => $registro->titular_nombre ? (string)$registro->titular_nombre : null,
                'monto_a_pagar' => (float)$registro->monto_a_pagar,
            ],
            'paciente' => $paciente ? $paciente->toArray() : null,
            'cuenta' => $cuenta ? [
                'id' => (int) $cuenta->id,
                'nro_cuenta' => (string) $cuenta->nro_cuenta,
                'estado' => $cuenta->estado !== null ? (string) $cuenta->estado : null,
                'bloqueada' => $cuentaBloqueada,
            ] : null,
            'bloqueada_facturacion' => $cuentaBloqueada,
            'servicios' => $serviciosPayload,
        ];
    }

    public function guardarAtencion(int $registroId, array $data): array
    {
        $registro = RegistroEmergencia::query()->findOrFail($registroId);
        $this->assertCuentaEditable($registro);

        $paciente = Paciente::query()
            ->where('numero_documento', $registro->numero_hc)
            ->orWhere('nr', $registro->numero_hc)
            ->first();

        $acudio = !empty($data['acudio_a_su_cita']);
        $horaAsistenciaRequest = isset($data['hora_asistencia']) ? trim((string)$data['hora_asistencia']) : null;
        $pacientePlanId = isset($data['paciente_plan_id']) ? (int)$data['paciente_plan_id'] : null;
        $parentescoSeguro = isset($data['parentesco_seguro']) ? trim((string)$data['parentesco_seguro']) : null;
        $titularNombre = isset($data['titular_nombre']) ? trim((string)$data['titular_nombre']) : null;
        
        $serviciosInput = $data['servicios'] ?? null;
        $montoAPagar = isset($data['monto_a_pagar']) ? (float)$data['monto_a_pagar'] : null;

        if (!$paciente) {
            throw ValidationException::withMessages([
                'numero_hc' => ['No se encontró el paciente del registro de emergencia. Regresa al registro y selecciona nuevamente al paciente.'],
            ]);
        }

        if (!$pacientePlanId) {
            throw ValidationException::withMessages([
                'paciente_plan_id' => ['Selecciona el tipo de cliente del paciente antes de guardar la atención de emergencia.'],
            ]);
        }

        if (!is_array($serviciosInput) || count($serviciosInput) === 0) {
            throw ValidationException::withMessages([
                'servicios' => ['Agrega al menos un servicio final antes de guardar la atención de emergencia.'],
            ]);
        }

        return DB::transaction(function () use ($registro, $paciente, $acudio, $horaAsistenciaRequest, $pacientePlanId, $parentescoSeguro, $titularNombre, $serviciosInput, $montoAPagar) {
            $nroCuenta = $registro->numero_cuenta;
            if ($nroCuenta === null || $nroCuenta === '') {
                $nroCuenta = $this->nroCuentaService->next();
            }

            $tarifaId = null;
            $plan = $paciente->planes()
                ->with('tipoCliente:id,iafa_id,tarifa_id')
                ->where('id', $pacientePlanId)
                ->where('estado', RecordStatus::ACTIVO->value)
                ->first();

            if (!$plan || !$plan->tipoCliente) {
                throw ValidationException::withMessages([
                    'paciente_plan_id' => ['El tipo de cliente seleccionado no pertenece al paciente o ya no está activo. Selecciona un plan vigente.'],
                ]);
            }

            $tarifaId = (int)$plan->tipoCliente->tarifa_id;
            if ($tarifaId <= 0) {
                throw ValidationException::withMessages([
                    'paciente_plan_id' => ['El tipo de cliente seleccionado no tiene una tarifa configurada para registrar la atención de emergencia.'],
                ]);
            }

            $horaAsistencia = null;
            if ($acudio) {
                if ($horaAsistenciaRequest !== '' && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $horaAsistenciaRequest)) {
                    $horaAsistencia = strlen($horaAsistenciaRequest) === 5 ? $horaAsistenciaRequest . ':00' : $horaAsistenciaRequest;
                } else {
                    $horaAsistencia = now()->format('H:i:s');
                }
            }

            $registro->numero_cuenta = $nroCuenta;
            if (!$registro->cuenta) {
                $registro->cuenta = $nroCuenta;
            }
            $registro->hora_asistencia = $horaAsistencia;
            $registro->paciente_plan_id = $pacientePlanId ?: null;
            $registro->tarifa_id = $tarifaId;
            $registro->parentesco_seguro = $parentescoSeguro ?: null;
            $registro->titular_nombre = $titularNombre ?: null;
            $registro->monto_a_pagar = $this->resolveMontoAPagar($montoAPagar, $serviciosInput);
            
            $registro->estado = 'ATENDIDO';
            $registro->save();

            $this->cuentaSyncService->syncFromRegistroEmergencia($registro->fresh());

            if (is_array($serviciosInput)) {
                $this->syncServicios($registro, $serviciosInput);
            }

            if ($paciente) {
                $paciente->parentesco_seguro = $parentescoSeguro ?: $paciente->parentesco_seguro;
                $paciente->titular_nombre = $titularNombre !== '' ? $titularNombre : $paciente->titular_nombre;
                $paciente->save();
            }

            $this->audit->log(
                'emergencia.atencion.save',
                'Guardar atención de emergencia',
                'registro_emergencia',
                (string)$registro->id,
                ['nro_cuenta' => $nroCuenta],
                'success',
                200
            );

            $this->realtime->entityChanged(
                module: 'emergencia',
                entity: 'atencion_emergencia',
                action: 'updated',
                id: (int) $registro->id,
                scope: (string) $nroCuenta,
                metadata: ['nro_cuenta' => $nroCuenta, 'estado' => $registro->estado],
            );

            $this->realtime->entityChanged(
                module: 'emergencia',
                entity: 'registro_emergencia',
                action: 'updated',
                id: (int) $registro->id,
                scope: (string) $nroCuenta,
                metadata: ['nro_cuenta' => $nroCuenta, 'estado' => $registro->estado],
            );

            return [
                'success' => true,
                'nro_cuenta' => $nroCuenta,
                'registro_id' => $registro->id,
            ];
        });
    }

    private function resolveMontoAPagar(?float $montoEnviado, ?array $serviciosInput): float
    {
        if ($montoEnviado !== null && $montoEnviado >= 0) {
            return round($montoEnviado, 4);
        }
        if (!is_array($serviciosInput)) {
            return 0;
        }
        $sum = 0;
        foreach ($serviciosInput as $s) {
            $estado = isset($s['estado_facturacion']) ? (string)$s['estado_facturacion'] : EstadoFacturacionServicio::PENDIENTE->value;
            if ($estado !== EstadoFacturacionServicio::PENDIENTE->value) {
                continue;
            }
            $sum += (float)($s['precio_con_igv'] ?? 0);
        }
        return round($sum, 4);
    }

    private function syncServicios(RegistroEmergencia $registro, array $servicios): void
    {
        RegistroEmergenciaServicio::query()->where('registro_emergencia_id', $registro->id)->delete();

        $userId = auth()->id();
        foreach ($servicios as $s) {
            $tarifaServicioId = (int)($s['tarifa_servicio_id'] ?? 0);
            $medicoId = (int)($s['medico_id'] ?? 0);
            if ($tarifaServicioId <= 0 || $medicoId <= 0) {
                continue;
            }
            $estadoFacturacion = isset($s['estado_facturacion']) && in_array((string)$s['estado_facturacion'], EstadoFacturacionServicio::values(), true)
                ? (string)$s['estado_facturacion']
                : EstadoFacturacionServicio::PENDIENTE->value;
            
            RegistroEmergenciaServicio::create([
                'registro_emergencia_id' => $registro->id,
                'tarifa_servicio_id' => $tarifaServicioId,
                'medico_id' => $medicoId,
                'user_id' => $userId,
                'cop_var' => (float)($s['cop_var'] ?? 0),
                'cop_fijo' => (float)($s['cop_fijo'] ?? 0),
                'descuento_pct' => (float)($s['descuento_pct'] ?? 0),
                'aumento_pct' => (float)($s['aumento_pct'] ?? 0),
                'cantidad' => (float)($s['cantidad'] ?? 1),
                'precio_sin_igv' => (float)($s['precio_sin_igv'] ?? 0),
                'precio_con_igv' => (float)($s['precio_con_igv'] ?? 0),
                'estado_facturacion' => $estadoFacturacion,
            ]);
        }
    }

    private function findCuenta(RegistroEmergencia $registro): ?Cuenta
    {
        return Cuenta::query()
            ->where('origen', 'REGISTRO_EMERGENCIA')
            ->where('origen_id', (int) $registro->id)
            ->first();
    }

    private function isCuentaBloqueada(?Cuenta $cuenta, ?string $nroCuenta): bool
    {
        if ($cuenta && strtoupper(trim((string) ($cuenta->estado ?? ''))) === 'CANCELADO') {
            return true;
        }

        $nro = trim((string) ($cuenta?->nro_cuenta ?? $nroCuenta ?? ''));
        if ($nro === '') {
            return false;
        }

        return EmisionComprobanteFacturacion::existeFacturadoraParaCuenta($nro);
    }

    private function assertCuentaEditable(RegistroEmergencia $registro): void
    {
        if (! $this->isCuentaBloqueada($this->findCuenta($registro), $registro->numero_cuenta)) {
            return;
        }

        throw ValidationException::withMessages([
            'cuenta' => ['La cuenta está cancelada y facturada. No se permiten modificaciones en la atención de emergencia.'],
        ]);
    }
}
