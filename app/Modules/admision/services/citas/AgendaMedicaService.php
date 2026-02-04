<?php

namespace App\Modules\admision\services\citas;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\models\Medico;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\models\ProgramacionMedica;
use App\Modules\admision\models\Turno;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AgendaMedicaService
{
    public function __construct(private AuditService $audit) {}

    public function opciones(array $filters): array
    {
        $fecha = trim((string)$filters['fecha']);
        $especialidadId = isset($filters['especialidad_id']) ? (int)$filters['especialidad_id'] : null;
        $medicoId = isset($filters['medico_id']) ? (int)$filters['medico_id'] : null;

        $query = ProgramacionMedica::query()
            ->with([
                'especialidad:id,codigo,descripcion',
                'medico:id,codigo,nombres,apellido_paterno,apellido_materno,cmp',
            ])
            ->whereDate('fecha', $fecha)
            ->where('estado', RecordStatus::ACTIVO->value);

        if ($especialidadId) {
            $query->where('especialidad_id', $especialidadId);
        }

        if ($medicoId) {
            $query->where('medico_id', $medicoId);
        }

        $items = $query->get();

        $programacionIds = $items->pluck('id')->all();
        $citasCount = [];
        if (count($programacionIds) > 0 && Schema::hasTable('agenda_citas')) {
            $citasCount = AgendaCita::query()
                ->whereIn('programacion_medica_id', $programacionIds)
                ->selectRaw('programacion_medica_id, count(*) as cnt')
                ->groupBy('programacion_medica_id')
                ->pluck('cnt', 'programacion_medica_id')
                ->all();
        }

        $cuposDisponiblesPorEspecialidad = [];
        foreach ($items as $pm) {
            $tomados = (int)($citasCount[$pm->id] ?? 0);
            $disponibles = max(0, (int)$pm->cupos - $tomados);
            $eid = $pm->especialidad_id;
            $cuposDisponiblesPorEspecialidad[$eid] = ($cuposDisponiblesPorEspecialidad[$eid] ?? 0) + $disponibles;
        }

        $especialidades = [];
        $medicos = [];

        foreach ($items as $pm) {
            if ($pm->especialidad) {
                $eid = (int)$pm->especialidad->id;
                $especialidades[$eid] = [
                    'id' => $eid,
                    'codigo' => (string)$pm->especialidad->codigo,
                    'descripcion' => (string)$pm->especialidad->descripcion,
                    'cupos_disponibles' => (int)($cuposDisponiblesPorEspecialidad[$eid] ?? 0),
                ];
            }
            if ($pm->medico) {
                $codigo = $pm->medico->getAttribute('codigo');
                $codigoStr = $codigo !== null ? trim((string)$codigo) : '';
                $medicos[$pm->medico->id] = [
                    'id' => (int)$pm->medico->id,
                    'codigo' => $codigoStr !== '' ? $codigoStr : null,
                    'nombres' => (string)$pm->medico->nombres,
                    'apellido_paterno' => (string)$pm->medico->apellido_paterno,
                    'apellido_materno' => (string)$pm->medico->apellido_materno,
                    'cmp' => $pm->medico->cmp !== null && trim((string)$pm->medico->cmp) !== '' ? (string)$pm->medico->cmp : null,
                ];
            }
        }

        return [
            'especialidades' => array_values($especialidades),
            'medicos' => array_values($medicos),
        ];
    }

    public function listarCitas(array $filters): array
    {
        if (!Schema::hasTable('agenda_citas')) {
            throw ValidationException::withMessages([
                'agenda_citas' => ['Falta ejecutar las migraciones de agenda de citas.'],
            ]);
        }

        $fecha = trim((string)($filters['fecha'] ?? ''));
        $especialidadId = isset($filters['especialidad_id']) ? (int)$filters['especialidad_id'] : null;
        $medicoId = isset($filters['medico_id']) ? (int)$filters['medico_id'] : null;

        if ($fecha === '' || !$especialidadId || !$medicoId) {
            return ['programacion' => null, 'paginator' => null];
        }

        $programacion = $this->resolveProgramacion($fecha, $especialidadId, $medicoId);
        if (!$programacion) {
            return ['programacion' => null, 'paginator' => null];
        }

        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $p = AgendaCita::query()
            ->where('programacion_medica_id', $programacion->id)
            ->with(['iafa:id,codigo,descripcion_corta,razon_social'])
            ->orderBy('hora', 'asc')
            ->paginate($perPage)
            ->appends([
                'fecha' => $fecha,
                'especialidad_id' => $especialidadId,
                'medico_id' => $medicoId,
                'per_page' => $perPage,
            ]);

        return ['programacion' => $programacion, 'paginator' => $p];
    }

    public function slots(array $filters): array
    {
        if (!Schema::hasTable('agenda_citas')) {
            throw ValidationException::withMessages([
                'agenda_citas' => ['Falta ejecutar las migraciones de agenda de citas.'],
            ]);
        }

        $fecha = trim((string)$filters['fecha']);
        $especialidadId = (int)$filters['especialidad_id'];
        $medicoId = (int)$filters['medico_id'];

        $programacion = $this->resolveProgramacion($fecha, $especialidadId, $medicoId);
        if (!$programacion) {
            throw ValidationException::withMessages([
                'programacion' => ['No existe programación médica para esa fecha.'],
            ]);
        }

        $programacion->load([
            'especialidad:id,codigo,descripcion',
            'medico:id,nombres,apellido_paterno,apellido_materno,tiempo_promedio_por_paciente,adicionales,extras',
            'turno:id,codigo,descripcion,hora_inicio,hora_fin,duracion_minutos',
            'consultorio:id,abreviatura,descripcion',
        ]);

        $slots = $this->buildSlots($programacion);
        $taken = AgendaCita::query()
            ->where('programacion_medica_id', $programacion->id)
            ->pluck('hora')
            ->map(fn ($v) => substr((string)$v, 0, 5))
            ->values()
            ->all();

        return [
            'programacion' => $programacion,
            'slots_base' => $slots['base'],
            'slots_adicional' => $slots['adicional'],
            'slots_extra' => $slots['extra'],
            'slots_tomados' => $taken,
            'tiempo_promedio' => $slots['tpp'],
            'adicionales' => $slots['adicionales'],
            'extras' => $slots['extras'],
        ];
    }

    public function crearCita(array $data): AgendaCita
    {
        if (!Schema::hasTable('agenda_citas')) {
            throw ValidationException::withMessages([
                'agenda_citas' => ['Falta ejecutar las migraciones de agenda de citas.'],
            ]);
        }

        $programacion = ProgramacionMedica::query()
            ->with([
                'especialidad:id,codigo,descripcion',
                'medico:id,nombres,apellido_paterno,apellido_materno,tiempo_promedio_por_paciente,adicionales,extras',
                'turno:id,codigo,descripcion,hora_inicio,hora_fin,duracion_minutos',
                'consultorio:id,abreviatura,descripcion',
            ])
            ->findOrFail((int)$data['programacion_medica_id']);

        if ($programacion->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['programacion_medica_id' => ['La programación debe estar ACTIVA.']]);
        }

        $hora = trim((string)$data['hora']);
        $slots = $this->buildSlots($programacion);
        $allowed = array_merge($slots['base'], $slots['adicional'], $slots['extra']);

        $taken = AgendaCita::query()
            ->where('programacion_medica_id', $programacion->id)
            ->pluck('hora')
            ->map(fn ($v) => substr((string)$v, 0, 5))
            ->values()
            ->all();

        $baseRemaining = array_values(array_diff($slots['base'], $taken));
        $adicionalRemaining = array_values(array_diff($slots['adicional'], $taken));

        if (in_array($hora, $slots['adicional'], true) && count($baseRemaining) > 0) {
            throw ValidationException::withMessages(['hora' => ['Debe agotar los cupos base antes de usar adicionales.']]);
        }

        if (in_array($hora, $slots['extra'], true) && (count($baseRemaining) > 0 || count($adicionalRemaining) > 0)) {
            throw ValidationException::withMessages(['hora' => ['Debe agotar los adicionales antes de usar extras.']]);
        }

        if (!in_array($hora, $allowed, true)) {
            throw ValidationException::withMessages(['hora' => ['La hora seleccionada no pertenece a la programación.']]);
        }

        $exists = in_array($hora, $taken, true);

        if ($exists) {
            throw ValidationException::withMessages(['hora' => ['La hora seleccionada ya está ocupada.']]);
        }

        $paciente = Paciente::query()->with(['planes.tipoCliente'])->findOrFail((int)$data['paciente_id']);

        $iafaId = isset($data['iafa_id']) ? (int)$data['iafa_id'] : null;
        if ($iafaId !== null) {
            $iafas = $paciente->planes
                ->filter(fn ($p) => $p->estado === RecordStatus::ACTIVO->value && $p->tipoCliente && $p->tipoCliente->iafa_id !== null)
                ->pluck('tipoCliente.iafa_id')
                ->map(fn ($v) => (int)$v)
                ->unique()
                ->values()
                ->all();

            if (!in_array($iafaId, $iafas, true)) {
                throw ValidationException::withMessages(['iafa_id' => ['La IAFAS no corresponde al paciente.']]);
            }
        }

        $orden = array_search($hora, $allowed, true);
        $orden = $orden === false ? 0 : ($orden + 1);

        $codigo = $this->nextCodigo((int)$programacion->id);

        return DB::transaction(function () use ($programacion, $paciente, $data, $hora, $orden, $codigo, $iafaId) {
            $cita = AgendaCita::create([
                'codigo' => $codigo,
                'programacion_medica_id' => (int)$programacion->id,
                'fecha' => $programacion->fecha,
                'hora' => $hora,
                'orden' => $orden,
                'paciente_id' => (int)$paciente->id,
                'hc' => $paciente->hc ?: null,
                'nr' => $paciente->nr ?: null,
                'paciente_nombre' => $paciente->nombre_completo,
                'sexo' => $paciente->sexo ?: null,
                'edad' => $paciente->edad,
                'titular_nombre' => $paciente->titular_nombre ?: null,
                'cuenta' => $data['cuenta'] ?? null,
                'iafa_id' => $iafaId,
                'motivo' => $data['motivo'] ?? null,
                'observacion' => $data['observacion'] ?? null,
                'autorizacion_siteds' => $data['autorizacion_siteds'] ?? null,
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'admision.citas.agenda_medica.create',
                'Crear cita',
                'agenda_citas',
                (string)$cita->id,
                [
                    'programacion_medica_id' => (int)$programacion->id,
                    'paciente_id' => (int)$paciente->id,
                    'hora' => $hora,
                    'orden' => $orden,
                ],
                'success',
                201
            );

            return $cita;
        });
    }

    private function resolveProgramacion(string $fecha, int $especialidadId, int $medicoId): ?ProgramacionMedica
    {
        return ProgramacionMedica::query()
            ->with([
                'especialidad:id,codigo,descripcion',
                'medico:id,nombres,apellido_paterno,apellido_materno,tiempo_promedio_por_paciente,adicionales,extras',
                'turno:id,codigo,descripcion,hora_inicio,hora_fin,duracion_minutos',
                'consultorio:id,abreviatura,descripcion',
            ])
            ->whereDate('fecha', $fecha)
            ->where('especialidad_id', $especialidadId)
            ->where('medico_id', $medicoId)
            ->where('estado', RecordStatus::ACTIVO->value)
            ->orderBy('turno_id')
            ->first();
    }

    private function buildSlots(ProgramacionMedica $programacion): array
    {
        $medico = $programacion->medico ?: Medico::query()->findOrFail((int)$programacion->medico_id);
        $turno = $programacion->turno ?: Turno::query()->findOrFail((int)$programacion->turno_id);

        $tppRaw = (int)($medico->tiempo_promedio_por_paciente ?? 0);
        if ($tppRaw <= 0) {
            throw ValidationException::withMessages([
                'tiempo_promedio_por_paciente' => ['El médico no tiene tiempo promedio por paciente válido.'],
            ]);
        }

        $cupos = (int)$programacion->cupos;
        if ($cupos <= 0) {
            throw ValidationException::withMessages([
                'cupos' => ['La programación no tiene cupos válidos.'],
            ]);
        }

        $horaInicio = $turno->hora_inicio ? substr((string)$turno->hora_inicio, 0, 5) : '';
        if (trim($horaInicio) === '') {
            throw ValidationException::withMessages([
                'turno_id' => ['El turno no tiene hora de inicio válida.'],
            ]);
        }

        $tpp = $tppRaw;
        $adicionales = max(0, (int)($medico->adicionales ?? 0));
        $extras = max(0, (int)($medico->extras ?? 0));

        try {
            $fecha = Carbon::parse($programacion->fecha)->toDateString();
            $start = Carbon::createFromFormat('Y-m-d H:i', $fecha . ' ' . $horaInicio);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'fecha' => ['La fecha/hora de programación no es válida.'],
            ]);
        }

        $base = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $cupos; $i++) {
            $base[] = $cursor->format('H:i');
            $cursor->addMinutes($tpp);
        }

        $baseEnd = $cursor->copy();

        $adicional = [];
        for ($i = 0; $i < $adicionales; $i++) {
            $adicional[] = $baseEnd->copy()->addMinutes($tpp * $i)->format('H:i');
        }

        $extraStart = $baseEnd->copy()->addMinutes($tpp * $adicionales);
        $extra = [];
        for ($i = 0; $i < $extras; $i++) {
            $extra[] = $extraStart->copy()->addMinutes($tpp * $i)->format('H:i');
        }

        return [
            'base' => $base,
            'adicional' => $adicional,
            'extra' => $extra,
            'tpp' => $tpp,
            'adicionales' => $adicionales,
            'extras' => $extras,
        ];
    }

    private function nextCodigo(int $programacionId): string
    {
        $last = AgendaCita::query()
            ->where('programacion_medica_id', $programacionId)
            ->select('codigo')
            ->orderByRaw('CAST(codigo AS BIGINT) DESC')
            ->value('codigo');

        $lastInt = $last !== null ? (int)$last : 0;
        $next = $lastInt + 1;

        return str_pad((string)$next, 3, '0', STR_PAD_LEFT);
    }
}
