<?php

namespace App\Modules\admision\services\citas;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\realtime\RealtimeBroadcaster;
use App\Core\support\ModalidadFechasProgramacion;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Medico;
use App\Modules\admision\models\ProgramacionMedica;
use App\Modules\admision\models\Turno;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgramacionMedicaService
{
    use AppliesListingQuery;

    public function __construct(
        private AuditService $audit,
        private RealtimeBroadcaster $realtime,
    ) {}

    public function paginate(GridParams $params): LengthAwarePaginator
    {
        $query = ProgramacionMedica::query()->with([
            'especialidad:id,codigo,descripcion',
            'medico:id,nombres,apellido_paterno,apellido_materno,tiempo_promedio_por_paciente',
            'turno:id,codigo,descripcion,duracion_minutos',
            'consultorio:id,abreviatura,descripcion',
        ]);

        $this->applyListingStatus($query, $params);

        $from = $params->filter('from');
        $to = $params->filter('to');
        if (is_string($from) && $from !== '') {
            $query->whereDate('fecha', '>=', $from);
        }
        if (is_string($to) && $to !== '') {
            $query->whereDate('fecha', '<=', $to);
        }

        if ($params->q !== null && $params->q !== '') {
            $this->applySearch($query, $params->q);
        }

        $this->applyListingSort(
            $query,
            $params,
            ['codigo', 'fecha', 'cupos', 'estado'],
            'fecha',
            ['codigo' => 'codigo']
        );

        return $query->orderBy('turno_id')->paginate($params->perPage, ['*'], 'page', $params->page);
    }

    public function calcularCupos(int $medicoId, int $turnoId): array
    {
        $medico = Medico::query()->findOrFail($medicoId);
        $turno = Turno::query()->findOrFail($turnoId);

        $dur = (int)($turno->duracion_minutos ?? 0);
        $tpp = (int)($medico->tiempo_promedio_por_paciente ?? 0);

        if ($dur <= 0) {
            throw ValidationException::withMessages(['turno_id' => ['El turno seleccionado no tiene una duración válida para calcular cupos.']]);
        }

        if ($tpp <= 0) {
            throw ValidationException::withMessages(['medico_id' => ['El médico seleccionado no tiene un tiempo promedio por paciente válido.']]);
        }

        $cupos = intdiv($dur, $tpp);

        if ($cupos < 1) {
            throw ValidationException::withMessages(['cupos' => ['Con la duración del turno y el tiempo promedio del médico no se puede generar al menos un cupo.']]);
        }

        return [
            'cupos' => $cupos,
            'duracion_minutos' => $dur,
            'tiempo_promedio_por_paciente' => $tpp,
        ];
    }

    public function createBatch(array $data): array
    {
        $fechas = $this->expandirFechas($data);

        if (count($fechas) > 370) {
            throw ValidationException::withMessages(['fechas' => ['La programación médica no puede generarse para más de 370 fechas a la vez.']]);
        }

        $medico = Medico::query()->findOrFail((int)$data['medico_id']);

        if ((int)$medico->especialidad_id !== (int)$data['especialidad_id']) {
            throw ValidationException::withMessages(['medico_id' => ['El médico seleccionado no corresponde a la especialidad elegida.']]);
        }

        $cuposInfo = $this->calcularCupos((int)$data['medico_id'], (int)$data['turno_id']);

        return DB::transaction(function () use ($data, $fechas, $cuposInfo) {
            $created = [];

            try {
                foreach ($fechas as $f) {
                    $pm = ProgramacionMedica::create([
                        'fecha' => $f,
                        'especialidad_id' => (int)$data['especialidad_id'],
                        'medico_id' => (int)$data['medico_id'],
                        'consultorio_id' => (int)$data['consultorio_id'],
                        'turno_id' => (int)$data['turno_id'],
                        'cupos' => (int)$cuposInfo['cupos'],
                        'tipo' => $data['tipo'],
                        'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
                    ]);

                    $created[] = $pm;
                }
            } catch (QueryException $e) {
                $msg = (string)$e->getMessage();

                if (str_contains($msg, 'pm_unique_medico_turno_fecha')) {
                    throw ValidationException::withMessages(['medico_id' => ['El médico ya tiene programación en ese turno para una de las fechas seleccionadas.']]);
                }

                if (str_contains($msg, 'pm_unique_consultorio_turno_fecha')) {
                    throw ValidationException::withMessages(['consultorio_id' => ['El consultorio ya está ocupado en ese turno para una de las fechas seleccionadas.']]);
                }

                throw $e;
            }

            $this->audit->log(
                'admision.citas.programacion_medica.create_batch',
                'Crear programación médica',
                'programacion_medica',
                'batch',
                [
                    'cantidad' => count($created),
                    'fechas' => $fechas,
                    'especialidad_id' => (int)$data['especialidad_id'],
                    'medico_id' => (int)$data['medico_id'],
                    'consultorio_id' => (int)$data['consultorio_id'],
                    'turno_id' => (int)$data['turno_id'],
                    'cupos' => (int)$cuposInfo['cupos'],
                    'tipo' => $data['tipo'],
                    'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
                ],
                'success',
                201
            );

            $ids = array_map(fn($x) => $x->id, $created);
            $this->realtime->entityChanged(
                module: 'admision',
                entity: 'programacion_medica',
                action: 'created',
                id: count($ids) === 1 ? (int) $ids[0] : 'batch',
                scope: (string) ($fechas[0] ?? ''),
                metadata: ['ids' => $ids, 'cantidad' => count($created), 'fechas' => $fechas],
            );

            $full = ProgramacionMedica::query()
                ->whereIn('id', $ids)
                ->with([
                    'especialidad:id,codigo,descripcion',
                    'medico:id,nombres,apellido_paterno,apellido_materno,tiempo_promedio_por_paciente',
                    'turno:id,codigo,descripcion,duracion_minutos',
                    'consultorio:id,abreviatura,descripcion',
                ])
                ->orderBy('fecha')
                ->get()
                ->values()
                ->all();

            return $full;
        });
    }

    public function update(ProgramacionMedica $pm, array $data): ProgramacionMedica
    {
        $medico = Medico::query()->findOrFail((int)$data['medico_id']);

        if ((int)$medico->especialidad_id !== (int)$data['especialidad_id']) {
            throw ValidationException::withMessages(['medico_id' => ['El médico seleccionado no corresponde a la especialidad elegida.']]);
        }

        $cuposInfo = $this->calcularCupos((int)$data['medico_id'], (int)$data['turno_id']);

        return DB::transaction(function () use ($pm, $data, $cuposInfo) {
            $before = $pm->only([
                'fecha',
                'especialidad_id',
                'medico_id',
                'consultorio_id',
                'turno_id',
                'cupos',
                'tipo',
                'estado',
            ]);

            $pm->fill([
                'fecha' => $data['fecha'],
                'especialidad_id' => (int)$data['especialidad_id'],
                'medico_id' => (int)$data['medico_id'],
                'consultorio_id' => (int)$data['consultorio_id'],
                'turno_id' => (int)$data['turno_id'],
                'cupos' => (int)$cuposInfo['cupos'],
                'tipo' => $data['tipo'],
                'estado' => $data['estado'],
            ]);

            try {
                $pm->save();
            } catch (QueryException $e) {
                $msg = (string)$e->getMessage();

                if (str_contains($msg, 'pm_unique_medico_turno_fecha')) {
                    throw ValidationException::withMessages(['medico_id' => ['El médico ya tiene programación en ese turno para esa fecha.']]);
                }

                if (str_contains($msg, 'pm_unique_consultorio_turno_fecha')) {
                    throw ValidationException::withMessages(['consultorio_id' => ['El consultorio ya está ocupado en ese turno para esa fecha.']]);
                }

                throw $e;
            }

            $after = $pm->only([
                'fecha',
                'especialidad_id',
                'medico_id',
                'consultorio_id',
                'turno_id',
                'cupos',
                'tipo',
                'estado',
            ]);

            $this->audit->log(
                'admision.citas.programacion_medica.update',
                'Actualizar programación médica',
                'programacion_medica',
                (string)$pm->id,
                [
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            $this->realtime->entityChanged(
                module: 'admision',
                entity: 'programacion_medica',
                action: 'updated',
                id: (int) $pm->id,
                scope: (string) $pm->fecha,
                metadata: ['before' => $before, 'after' => $after],
            );

            return $pm->load([
                'especialidad:id,codigo,descripcion',
                'medico:id,nombres,apellido_paterno,apellido_materno,tiempo_promedio_por_paciente',
                'turno:id,codigo,descripcion,duracion_minutos',
                'consultorio:id,abreviatura,descripcion',
            ]);
        });
    }

    public function deactivate(ProgramacionMedica $pm): ProgramacionMedica
    {
        return DB::transaction(function () use ($pm) {
            $before = $pm->only(['estado']);

            $pm->estado = RecordStatus::INACTIVO->value;
            $pm->save();

            $this->audit->log(
                'admision.citas.programacion_medica.deactivate',
                'Desactivar programación médica',
                'programacion_medica',
                (string)$pm->id,
                [
                    'before' => $before,
                    'after' => $pm->only(['estado']),
                ],
                'success',
                200
            );

            $this->realtime->entityChanged(
                module: 'admision',
                entity: 'programacion_medica',
                action: 'disabled',
                id: (int) $pm->id,
                scope: (string) $pm->fecha,
                metadata: ['estado' => $pm->estado],
            );

            return $pm->load([
                'especialidad:id,codigo,descripcion',
                'medico:id,nombres,apellido_paterno,apellido_materno,tiempo_promedio_por_paciente',
                'turno:id,codigo,descripcion,duracion_minutos',
                'consultorio:id,abreviatura,descripcion',
            ]);
        });
    }

    private function expandirFechas(array $data): array
    {
        $m = strtoupper((string)$data['modalidad_fechas']);

        if ($m === ModalidadFechasProgramacion::DIARIA->value) {
            return [(string)$data['fecha']];
        }

        if ($m === ModalidadFechasProgramacion::ALEATORIA->value) {
            $fechas = $data['fechas'] ?? [];
            $fechas = array_values(array_unique(array_map('strval', $fechas)));
            sort($fechas);
            return $fechas;
        }

        if ($m === ModalidadFechasProgramacion::RANGO->value) {
            $ini = Carbon::parse((string)$data['fecha_inicio'])->startOfDay();
            $fin = Carbon::parse((string)$data['fecha_fin'])->startOfDay();

            $out = [];
            $cur = $ini->copy();

            while ($cur->lessThanOrEqualTo($fin)) {
                $out[] = $cur->format('Y-m-d');
                $cur->addDay();
            }

            return $out;
        }

        throw ValidationException::withMessages(['modalidad_fechas' => ['La modalidad de fechas seleccionada no es válida para programación médica.']]);
    }

    private function applySearch(Builder $query, string $q): void
    {
        $q = trim($q);
        if ($q === '') return;

        $qLike = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q) . '%';

        $qInt = null;
        if (preg_match('/^\s*0*\d+\s*$/', $q)) {
            $qInt = (int)$q;
        }

        $qDate = $this->tryParseDateToYmd($q);

        $query->where(function (Builder $w) use ($qLike, $qInt, $qDate) {

            if ($qInt !== null && $qInt > 0) {
                $w->orWhere('id', $qInt);
                $w->orWhere('cupos', $qInt);
            }

            if ($qDate !== null) {
                $w->orWhereDate('fecha', $qDate);
            }

            $w->orWhereHas('medico', function (Builder $m) use ($qLike) {
                $m->where('nombres', 'ilike', $qLike)
                    ->orWhere('apellido_paterno', 'ilike', $qLike)
                    ->orWhere('apellido_materno', 'ilike', $qLike);
            });

            $w->orWhereHas('especialidad', function (Builder $e) use ($qLike) {
                $e->where('codigo', 'ilike', $qLike)
                    ->orWhere('descripcion', 'ilike', $qLike);
            });

            $w->orWhereHas('consultorio', function (Builder $c) use ($qLike) {
                $c->where('abreviatura', 'ilike', $qLike)
                    ->orWhere('descripcion', 'ilike', $qLike);
            });

            $w->orWhereHas('turno', function (Builder $t) use ($qLike) {
                $t->where('codigo', 'ilike', $qLike)
                    ->orWhere('descripcion', 'ilike', $qLike);
            });
        });
    }

    private function tryParseDateToYmd(string $q): ?string
    {
        $q = trim($q);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $q)) {
            return $q;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $q, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        return null;
    }
}
