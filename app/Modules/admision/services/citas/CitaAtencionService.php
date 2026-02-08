<?php

namespace App\Modules\admision\services\citas;

use App\Core\audit\AuditService;
use App\Core\support\CitaAtencionEstado;
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
    public function __construct(private AuditService $audit) {}

    /**
     * Datos para el formulario de Atención de cita (solo lectura + planes + atencion existente).
     */
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
                             ->with(['tipoCliente:id,codigo,descripcion_tipo_cliente,tarifa_id', 'tipoCliente.tarifa:id,codigo,descripcion_tarifa']);
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
                'descripcion' => $tc ? ($tc->codigo . '/' . ($tc->descripcion_tipo_cliente ?? '')) : '',
                'tarifa_id' => $tarifa ? (int)$tarifa->id : null,
                'tarifa_codigo' => $tarifa ? (string)$tarifa->codigo : null,
                'tarifa_descripcion' => $tarifa ? (string)($tarifa->descripcion_tarifa ?? $tarifa->codigo ?? '') : null,
            ];
        })->values()->all();

        $atencion = CitaAtencion::query()
            ->where('agenda_cita_id', $agendaCitaId)
            ->first();

        $serviciosPayload = [];
        if ($atencion) {
            $servicios = CitaAtencionServicio::query()
                ->where('cita_atencion_id', $atencion->id)
                ->with(['tarifaServicio:id,codigo,descripcion,precio_sin_igv', 'medico:id,codigo,nombres,apellido_paterno,apellido_materno', 'user:id,name,username'])
                ->get();
            foreach ($servicios as $s) {
                $ts = $s->tarifaServicio;
                $med = $s->medico;
                $serviciosPayload[] = [
                    'id' => (int)$s->id,
                    'tarifa_servicio_id' => (int)$s->tarifa_servicio_id,
                    'servicio_codigo' => $ts ? (string)$ts->codigo : null,
                    'servicio_descripcion' => $ts ? (string)$ts->descripcion : null,
                    'medico_id' => (int)$s->medico_id,
                    'medico_codigo' => $med ? (string)$med->codigo : null,
                    'medico_nombre' => $med ? trim($med->apellido_paterno . ' ' . $med->apellido_materno . ' ' . $med->nombres) : null,
                    'user_id' => $s->user_id ? (int)$s->user_id : null,
                    'user_nombre' => $s->user ? (string)$s->user->username : null,
                    'cop_var' => (float)$s->cop_var,
                    'cop_fijo' => (float)$s->cop_fijo,
                    'descuento_pct' => (float)$s->descuento_pct,
                    'aumento_pct' => (float)$s->aumento_pct,
                    'cantidad' => (float)$s->cantidad,
                    'precio_sin_igv' => (float)$s->precio_sin_igv,
                    'precio_con_igv' => (float)$s->precio_con_igv,
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
            ] : null,
            'servicios' => $serviciosPayload,
        ];
    }

    /**
     * Solo actualizar datos editables: paciente (parentesco, titular) y atencion (plan, parentesco, titular).
     * No genera nro_cuenta ni marca estado_atencion.
     */
    public function actualizarSoloDatos(int $agendaCitaId, array $data): array
    {
        $cita = AgendaCita::query()->with(['paciente.planes' => function ($q) {
            $q->with('tipoCliente:id,codigo,descripcion_tipo_cliente,tarifa_id');
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
        $indicadores = [
            'control_pre_post_natal' => !empty($data['control_pre_post_natal']),
            'control_nino_sano' => !empty($data['control_nino_sano']),
            'chequeo' => !empty($data['chequeo']),
            'carencia' => !empty($data['carencia']),
            'latencia' => !empty($data['latencia']),
        ];

        return DB::transaction(function () use ($cita, $paciente, $pacientePlanId, $parentescoSeguro, $titularNombre, $serviciosInput, $indicadores) {
            $tarifaId = null;
            if ($pacientePlanId) {
                $plan = $paciente->planes()->where('id', $pacientePlanId)->first();
                if ($plan && $plan->tipoCliente) {
                    $tarifaId = (int)$plan->tipoCliente->tarifa_id;
                }
            }

            $atencion = CitaAtencion::query()->where('agenda_cita_id', $cita->id)->first();
            $atencionPayload = array_merge([
                'paciente_plan_id' => $pacientePlanId ?: null,
                'tarifa_id' => $tarifaId,
                'parentesco_seguro' => $parentescoSeguro ?: null,
                'titular_nombre' => $titularNombre ?: null,
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

    /**
     * Guardar atención: genera nro_cuenta, crea/actualiza cita_atenciones, actualiza agenda_cita y paciente.
     * Hora del servidor (configurable por timezone) para hora_asistencia.
     */
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

        return DB::transaction(function () use ($cita, $paciente, $acudio, $horaAsistenciaRequest, $pacientePlanId, $parentescoSeguro, $titularNombre, $indicadores, $serviciosInput) {
            $atencion = CitaAtencion::query()->where('agenda_cita_id', $cita->id)->first();

            $nroCuenta = $atencion?->nro_cuenta;
            if ($nroCuenta === null || $nroCuenta === '') {
                $nroCuenta = $this->nextNroCuenta();
            }

            $tarifaId = null;
            if ($pacientePlanId) {
                $plan = $paciente->planes()->where('id', $pacientePlanId)->first();
                if ($plan && $plan->tipoCliente) {
                    $tarifaId = (int)$plan->tipoCliente->tarifa_id;
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

    private function nextNroCuenta(): string
    {
        $last = CitaAtencion::query()
            ->whereNotNull('nro_cuenta')
            ->where('nro_cuenta', '!=', '')
            ->orderByRaw('CAST(nro_cuenta AS UNSIGNED) DESC')
            ->value('nro_cuenta');

        $nextInt = $last !== null ? (int)$last + 1 : 1;
        return str_pad((string)$nextInt, 10, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<int, array{tarifa_servicio_id: int, medico_id: int, cop_var?: float, cop_fijo?: float, descuento_pct?: float, aumento_pct?: float, cantidad?: float, precio_sin_igv: float, precio_con_igv: float}> $servicios
     */
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
