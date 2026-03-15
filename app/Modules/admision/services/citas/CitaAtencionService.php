<?php

namespace App\Modules\admision\services\citas;

use App\Core\audit\AuditService;
use App\Core\NroCuentaService;
use App\Core\support\CitaAtencionEstado;
use App\Core\support\EstadoFacturacionServicio;
use App\Core\support\RecordStatus;
use App\Core\support\SexoPaciente;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\models\CitaAtencion;
use App\Modules\admision\models\CitaAtencionServicio;
use App\Modules\admision\models\Paciente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CitaAtencionService
{
    public function __construct(
        private AuditService $audit,
        private NroCuentaService $nroCuentaService
    ) {}

    public function datosParaAtencion(int $agendaCitaId): array
    {
        $cita = AgendaCita::query()
            ->with([
                'programacion.especialidad:id,codigo,descripcion',
                'programacion.medico:id,nombres,apellido_paterno,apellido_materno',
                'programacion.consultorio:id,abreviatura,descripcion',
                'paciente' => function ($q) {
                    $q->select('id', 'tipo_documento', 'numero_documento', 'nr', 'nombres', 'apellido_paterno', 'apellido_materno', 'sexo', 'fecha_nacimiento', 'edad', 'parentesco_seguro', 'titular_nombre', 'celular', 'telefono', 'email')
                      ->with(['planes' => function ($q2) {
                          $q2->where('estado', RecordStatus::ACTIVO->value)
                             ->with(['tipoCliente:id,codigo,descripcion_tipo_cliente,tarifa_id,iafa_id', 'tipoCliente.tarifa:id,codigo,descripcion_tarifa,es_precio_directo']);
                      }]);
                },
                'iafa:id,codigo,descripcion_corta,razon_social',
            ])
            ->findOrFail($agendaCitaId);

        if ($cita->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'cita' => ['La cita no está activa o fue anulada.'],
            ]);
        }

        $paciente = $cita->paciente;
        if (!$paciente) {
            throw ValidationException::withMessages(['cita' => ['Paciente no encontrado.']]);
        }

        $planes = $paciente->planes ?? collect();

        $planesPayload = $planes->map(function ($plan) {
            $tc = $plan->tipoCliente;
            $tarifa = $tc?->tarifa;
            return [
                'id' => (int)$plan->id,
                'tipo_cliente_id' => (int)$plan->tipo_cliente_id,
                'iafa_id' => $tc && $tc->iafa_id ? (int)$tc->iafa_id : null,
                'descripcion' => $tc ? ($tc->codigo . '/' . ($tc->descripcion_tipo_cliente ?? '')) : '',
                'tarifa_id' => $tarifa ? (int)$tarifa->id : null,
                'tarifa_codigo' => $tarifa ? (string)$tarifa->codigo : null,
                'tarifa_descripcion' => $tarifa ? (string)($tarifa->descripcion_tarifa ?? $tarifa->codigo ?? '') : null,
                'tarifa_es_precio_directo' => $tarifa ? (bool)$tarifa->es_precio_directo : false,
            ];
        })->values()->all();

        $atencion = CitaAtencion::query()
            ->where('agenda_cita_id', $agendaCitaId)
            ->first();

        $serviciosPayload = [];
        if ($atencion) {
            $servicios = CitaAtencionServicio::query()
                ->where('cita_atencion_id', $atencion->id)
                ->with(['tarifaServicio:id,codigo,descripcion,precio_sin_igv,desea_liberar_precio,categoria_id', 'tarifaServicio.categoria:id,codigo', 'medico:id,codigo,nombres,apellido_paterno,apellido_materno', 'user:id,name,username,nombres,apellido_paterno,apellido_materno'])
                ->get();
            foreach ($servicios as $s) {
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

        $programacion = $cita->programacion;
        $horaStr = $cita->hora ? (is_string($cita->hora) ? substr($cita->hora, 0, 5) : $cita->hora->format('H:i')) : null;

        return [
            'cita' => [
                'id' => (int)$cita->id,
                'codigo' => (string)$cita->codigo,
                'fecha' => $cita->fecha?->format('Y-m-d'),
                'hora' => $horaStr,
                'orden' => (int)$cita->orden,
                'motivo' => $cita->motivo ? (string)$cita->motivo : null,
                'autorizacion_siteds' => $cita->autorizacion_siteds ? (string)$cita->autorizacion_siteds : null,
                'cuenta' => $cita->cuenta ? (string)$cita->cuenta : null,
                'estado_atencion' => (string)$cita->estado_atencion,
                'iafa_id' => $cita->iafa_id ? (int)$cita->iafa_id : null,
            ],
            'programacion' => $programacion ? [
                'id' => (int)$programacion->id,
                'especialidad' => $programacion->especialidad ? [
                    'id' => (int)$programacion->especialidad->id,
                    'codigo' => (string)$programacion->especialidad->codigo,
                    'descripcion' => (string)$programacion->especialidad->descripcion,
                ] : null,
                'medico' => $programacion->medico ? [
                    'id' => (int)$programacion->medico->id,
                    'nombres' => (string)$programacion->medico->nombres,
                    'apellido_paterno' => (string)$programacion->medico->apellido_paterno,
                    'apellido_materno' => (string)$programacion->medico->apellido_materno,
                ] : null,
                'consultorio' => $programacion->consultorio ? [
                    'id' => (int)$programacion->consultorio->id,
                    'abreviatura' => (string)$programacion->consultorio->abreviatura,
                    'descripcion' => (string)$programacion->consultorio->descripcion,
                ] : null,
            ] : null,
            'paciente' => [
                'id' => (int)$paciente->id,
                'numero_documento' => $paciente->numero_documento ? (string)$paciente->numero_documento : null,
                'nr' => $paciente->nr ? (string)$paciente->nr : null,
                'edad' => $paciente->edad ?? (isset($paciente->fecha_nacimiento) ? $this->calcularEdad($paciente->fecha_nacimiento) : null),
                'sexo' => SexoPaciente::formatForDisplay($paciente->sexo),
                'apellidos_nombres' => trim($paciente->apellido_paterno . ' ' . $paciente->apellido_materno . ' ' . $paciente->nombres),
                'parentesco_seguro' => $paciente->parentesco_seguro ? (string)$paciente->parentesco_seguro : null,
                'titular_nombre' => $paciente->titular_nombre ? (string)$paciente->titular_nombre : null,
                'celular' => $paciente->celular ? (string)$paciente->celular : null,
                'telefono' => $paciente->telefono ? (string)$paciente->telefono : null,
                'email' => $paciente->email ? (string)$paciente->email : null,
            ],
            'planes' => $planesPayload,
            'atencion' => $atencion ? [
                'id' => (int)$atencion->id,
                'nro_cuenta' => $atencion->nro_cuenta ? (string)$atencion->nro_cuenta : null,
                'hora_asistencia' => $atencion->hora_asistencia ? (is_string($atencion->hora_asistencia) ? substr($atencion->hora_asistencia, 0, 5) : $atencion->hora_asistencia->format('H:i')) : null,
                'paciente_plan_id' => $atencion->paciente_plan_id ? (int)$atencion->paciente_plan_id : null,
                'tarifa_id' => $atencion->tarifa_id ? (int)$atencion->tarifa_id : null,
                'parentesco_seguro' => $atencion->parentesco_seguro ? (string)$atencion->parentesco_seguro : null,
                'titular_nombre' => $atencion->titular_nombre ? (string)$atencion->titular_nombre : null,
                'control_pre_post_natal' => (bool)$atencion->control_pre_post_natal,
                'control_nino_sano' => (bool)$atencion->control_nino_sano,
                'chequeo' => (bool)$atencion->chequeo,
                'carencia' => (bool)$atencion->carencia,
                'latencia' => (bool)$atencion->latencia,
                'monto_a_pagar' => (float)$atencion->monto_a_pagar,
                'soat_activo' => (bool)$atencion->soat_activo,
                'soat_numero_poliza' => $atencion->soat_numero_poliza ? (string)$atencion->soat_numero_poliza : null,
                'soat_numero_placa' => $atencion->soat_numero_placa ? (string)$atencion->soat_numero_placa : null,
            ] : null,
            'servicios' => $serviciosPayload,
        ];
    }

    public function actualizarSoloDatos(int $agendaCitaId, array $data): array
    {
        $cita = AgendaCita::query()->with(['paciente.planes' => function ($q) {
            $q->with('tipoCliente:id,codigo,descripcion_tipo_cliente,tarifa_id,iafa_id');
        }])->findOrFail($agendaCitaId);

        if ($cita->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['cita' => ['La cita no está activa.']]);
        }

        $paciente = $cita->paciente;
        if (!$paciente) {
            throw ValidationException::withMessages(['cita' => ['Paciente no encontrado.']]);
        }

        $pacientePlanId = isset($data['paciente_plan_id']) ? (int)$data['paciente_plan_id'] : null;
        $parentescoSeguro = isset($data['parentesco_seguro']) ? trim((string)$data['parentesco_seguro']) : null;
        $titularNombre = isset($data['titular_nombre']) ? trim((string)$data['titular_nombre']) : null;
        $serviciosInput = $data['servicios'] ?? null;
        $montoAPagar = isset($data['monto_a_pagar']) ? (float)$data['monto_a_pagar'] : null;
        $soatActivo = !empty($data['soat_activo']);
        $soatNumeroPoliza = isset($data['soat_numero_poliza']) ? trim((string)$data['soat_numero_poliza']) : null;
        $soatNumeroPlaca = isset($data['soat_numero_placa']) ? trim((string)$data['soat_numero_placa']) : null;
        $indicadores = [
            'control_pre_post_natal' => !empty($data['control_pre_post_natal']),
            'control_nino_sano' => !empty($data['control_nino_sano']),
            'chequeo' => !empty($data['chequeo']),
            'carencia' => !empty($data['carencia']),
            'latencia' => !empty($data['latencia']),
        ];

        return DB::transaction(function () use ($cita, $paciente, $pacientePlanId, $parentescoSeguro, $titularNombre, $serviciosInput, $montoAPagar, $soatActivo, $soatNumeroPoliza, $soatNumeroPlaca, $indicadores) {
            $tarifaId = null;
            $iafaId = null;
            if ($pacientePlanId) {
                $plan = $paciente->planes()->where('id', $pacientePlanId)->first();
                if ($plan && $plan->tipoCliente) {
                    $tarifaId = (int)$plan->tipoCliente->tarifa_id;
                    $iafaId = $plan->tipoCliente->iafa_id ? (int)$plan->tipoCliente->iafa_id : null;
                }
            }

            $atencion = CitaAtencion::query()->where('agenda_cita_id', $cita->id)->first();
            $atencionPayload = array_merge([
                'paciente_plan_id' => $pacientePlanId ?: null,
                'tarifa_id' => $tarifaId,
                'parentesco_seguro' => $parentescoSeguro ?: null,
                'titular_nombre' => $titularNombre ?: null,
                'monto_a_pagar' => $this->resolveMontoAPagar($montoAPagar, $serviciosInput),
                'soat_activo' => $soatActivo,
                'soat_numero_poliza' => $soatActivo && $soatNumeroPoliza !== '' ? $soatNumeroPoliza : null,
                'soat_numero_placa' => $soatActivo && $soatNumeroPlaca !== '' ? $soatNumeroPlaca : null,
            ], $indicadores);
            if ($atencion) {
                $atencion->update($atencionPayload);
                if (is_array($serviciosInput)) {
                    $this->syncServicios($atencion, $serviciosInput);
                }
            } else {
                $atencion = CitaAtencion::create(array_merge([
                    'agenda_cita_id' => $cita->id,
                ], $atencionPayload));
                if (is_array($serviciosInput) && !empty($serviciosInput)) {
                    $this->syncServicios($atencion, $serviciosInput);
                }
            }

            $cita->iafa_id = $iafaId;
            $cita->save();

            $paciente->parentesco_seguro = $parentescoSeguro ?: $paciente->parentesco_seguro;
            $paciente->titular_nombre = $titularNombre !== '' ? $titularNombre : $paciente->titular_nombre;
            if ($paciente->fecha_nacimiento) {
                $paciente->edad = $this->calcularEdad($paciente->fecha_nacimiento);
            }
            $paciente->save();

            $this->audit->log(
                'admision.citas.atencion.actualizar_datos',
                'Actualizar datos de atención (plan, condición, titular)',
                'cita_atenciones',
                $atencion ? (string)$atencion->id : '0',
                ['agenda_cita_id' => $cita->id],
                'success',
                200
            );

            return $this->datosParaAtencion((int)$cita->id);
        });
    }

    public function guardarAtencion(int $agendaCitaId, array $data): array
    {
        $cita = AgendaCita::query()->with(['paciente'])->findOrFail($agendaCitaId);

        if ($cita->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['cita' => ['La cita no está activa.']]);
        }

        $paciente = $cita->paciente;
        if (!$paciente) {
            throw ValidationException::withMessages(['cita' => ['Paciente no encontrado.']]);
        }

        $acudio = !empty($data['acudio_a_su_cita']);
        $horaAsistenciaRequest = isset($data['hora_asistencia']) ? trim((string)$data['hora_asistencia']) : null;
        $pacientePlanId = isset($data['paciente_plan_id']) ? (int)$data['paciente_plan_id'] : null;
        $parentescoSeguro = isset($data['parentesco_seguro']) ? trim((string)$data['parentesco_seguro']) : null;
        $titularNombre = isset($data['titular_nombre']) ? trim((string)$data['titular_nombre']) : null;
        $indicadores = [
            'control_pre_post_natal' => !empty($data['control_pre_post_natal']),
            'control_nino_sano' => !empty($data['control_nino_sano']),
            'chequeo' => !empty($data['chequeo']),
            'carencia' => !empty($data['carencia']),
            'latencia' => !empty($data['latencia']),
        ];
        $serviciosInput = $data['servicios'] ?? null;
        $montoAPagar = isset($data['monto_a_pagar']) ? (float)$data['monto_a_pagar'] : null;
        $soatActivo = !empty($data['soat_activo']);
        $soatNumeroPoliza = isset($data['soat_numero_poliza']) ? trim((string)$data['soat_numero_poliza']) : null;
        $soatNumeroPlaca = isset($data['soat_numero_placa']) ? trim((string)$data['soat_numero_placa']) : null;

        return DB::transaction(function () use ($cita, $paciente, $acudio, $horaAsistenciaRequest, $pacientePlanId, $parentescoSeguro, $titularNombre, $indicadores, $serviciosInput, $montoAPagar, $soatActivo, $soatNumeroPoliza, $soatNumeroPlaca) {
            $atencion = CitaAtencion::query()->where('agenda_cita_id', $cita->id)->first();

            $nroCuenta = $atencion?->nro_cuenta;
            if ($nroCuenta === null || $nroCuenta === '') {
                $nroCuenta = $this->nroCuentaService->next();
            }

            $tarifaId = null;
            $iafaId = null;
            if ($pacientePlanId) {
                $plan = $paciente->planes()->with('tipoCliente:id,iafa_id,tarifa_id')->where('id', $pacientePlanId)->first();
                if ($plan && $plan->tipoCliente) {
                    $tarifaId = (int)$plan->tipoCliente->tarifa_id;
                    $iafaId = $plan->tipoCliente->iafa_id ? (int)$plan->tipoCliente->iafa_id : null;
                }
            }

            $horaAsistencia = null;
            if ($acudio) {
                if ($horaAsistenciaRequest !== '' && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $horaAsistenciaRequest)) {
                    $horaAsistencia = strlen($horaAsistenciaRequest) === 5 ? $horaAsistenciaRequest . ':00' : $horaAsistenciaRequest;
                } else {
                    $horaAsistencia = now()->format('H:i:s');
                }
            }

            $atencionPayload = array_merge([
                'nro_cuenta' => $nroCuenta,
                'hora_asistencia' => $horaAsistencia,
                'paciente_plan_id' => $pacientePlanId ?: null,
                'tarifa_id' => $tarifaId,
                'parentesco_seguro' => $parentescoSeguro ?: null,
                'titular_nombre' => $titularNombre ?: null,
                'monto_a_pagar' => $this->resolveMontoAPagar($montoAPagar, $serviciosInput),
                'soat_activo' => $soatActivo,
                'soat_numero_poliza' => $soatActivo && $soatNumeroPoliza !== '' ? $soatNumeroPoliza : null,
                'soat_numero_placa' => $soatActivo && $soatNumeroPlaca !== '' ? $soatNumeroPlaca : null,
            ], $indicadores);

            if ($atencion) {
                $atencion->update($atencionPayload);
            } else {
                $atencion = CitaAtencion::create(array_merge([
                    'agenda_cita_id' => $cita->id,
                ], $atencionPayload));
            }

            if (is_array($serviciosInput)) {
                $this->syncServicios($atencion, $serviciosInput);
            }

            $cita->cuenta = $nroCuenta;
            $cita->estado_atencion = CitaAtencionEstado::ATENDIDO->value;
            $cita->iafa_id = $iafaId;
            $cita->save();

            $paciente->parentesco_seguro = $parentescoSeguro ?: $paciente->parentesco_seguro;
            $paciente->titular_nombre = $titularNombre !== '' ? $titularNombre : $paciente->titular_nombre;
            if ($paciente->fecha_nacimiento) {
                $paciente->edad = $this->calcularEdad($paciente->fecha_nacimiento);
            }
            $paciente->save();

            $this->audit->log(
                'admision.citas.atencion.save',
                'Guardar atención de cita',
                'cita_atenciones',
                (string)$atencion->id,
                ['agenda_cita_id' => $cita->id, 'nro_cuenta' => $nroCuenta],
                'success',
                200
            );

            return $this->datosParaAtencion((int)$cita->id);
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

    private function syncServicios(CitaAtencion $atencion, array $servicios): void
    {
        CitaAtencionServicio::query()->where('cita_atencion_id', $atencion->id)->delete();

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
            CitaAtencionServicio::create([
                'cita_atencion_id' => $atencion->id,
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

    private function calcularEdad($fechaNacimiento): ?int
    {
        if (!$fechaNacimiento) {
            return null;
        }
        $d = $fechaNacimiento instanceof \Carbon\Carbon
            ? $fechaNacimiento
            : \Carbon\Carbon::parse($fechaNacimiento);
        $age = $d->diffInYears(now()->startOfDay());
        return $age >= 0 ? $age : null;
    }
}
