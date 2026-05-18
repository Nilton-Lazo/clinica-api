<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\Turno;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TurnoService
{
    use AppliesListingQuery;

    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        return CodigoCorrelativo::format($n);
    }

    public function previewNextCodigo(): string
    {
        $last = Turno::query()
            ->whereRaw("codigo ~ '^[0-9]+$'")
            ->orderByRaw('codigo::int desc')
            ->value('codigo');

        return CodigoCorrelativo::nextFromLast($last);
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'ficheros:turnos:version';

    private function getListCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 0);
    }

    private function invalidateListCache(): void
    {
        Cache::put(self::CACHE_VERSION_KEY, $this->getListCacheVersion() + 1, 86400);
    }

    public function paginate(GridParams $params): LengthAwarePaginator
    {
        $version = $this->getListCacheVersion();
        $cacheKey = 'ficheros:turnos:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = Turno::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'descripcion']);
            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): Turno
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE turnos IN EXCLUSIVE MODE');

            $last = Turno::query()
                ->whereRaw("codigo ~ '^[0-9]+$'")
                ->orderByRaw('codigo::int desc')
                ->value('codigo');
            $codigo = CodigoCorrelativo::nextFromLast($last);

            [$duracionMin, $hi, $hf] = $this->calcDurationMinutes($data['hora_inicio'], $data['hora_fin']);
            $auto = $this->buildDescripcion($codigo, $hi, $hf);

            $descIn = isset($data['descripcion']) ? trim((string)$data['descripcion']) : '';
            $descripcion = $descIn !== '' ? $descIn : $auto;

            $turno = Turno::create([
                'codigo' => $codigo,
                'hora_inicio' => $hi,
                'hora_fin' => $hf,
                'duracion_minutos' => $duracionMin,
                'descripcion' => $descripcion,
                'tipo_turno' => $data['tipo_turno'],
                'jornada' => $data['jornada'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.turnos.create',
                'Crear turno',
                'turno',
                (string)$turno->id,
                $turno->only([
                    'codigo',
                    'hora_inicio',
                    'hora_fin',
                    'duracion_minutos',
                    'descripcion',
                    'tipo_turno',
                    'jornada',
                    'estado',
                ]),
                'success',
                201
            );

            $this->invalidateListCache();
            return $turno;
        });
    }

    public function update(Turno $turno, array $data): Turno
    {
        return DB::transaction(function () use ($turno, $data) {
            $before = $turno->only([
                'hora_inicio',
                'hora_fin',
                'duracion_minutos',
                'descripcion',
                'tipo_turno',
                'jornada',
                'estado',
            ]);

            [$duracionMin, $hi, $hf] = $this->calcDurationMinutes($data['hora_inicio'], $data['hora_fin']);
            $auto = $this->buildDescripcion($turno->codigo, $hi, $hf);

            $attrs = [
                'hora_inicio' => $hi,
                'hora_fin' => $hf,
                'duracion_minutos' => $duracionMin,
                'tipo_turno' => $data['tipo_turno'],
                'jornada' => $data['jornada'],
                'estado' => $data['estado'],
            ];

            if (array_key_exists('descripcion', $data)) {
                $descIn = $data['descripcion'] === null ? '' : trim((string)$data['descripcion']);
                $attrs['descripcion'] = $descIn !== '' ? $descIn : $auto;
            }

            $turno->fill($attrs);
            $turno->save();

            $after = $turno->only([
                'hora_inicio',
                'hora_fin',
                'duracion_minutos',
                'descripcion',
                'tipo_turno',
                'jornada',
                'estado',
            ]);

            $this->audit->log(
                'masterdata.admision.turnos.update',
                'Actualizar turno',
                'turno',
                (string)$turno->id,
                [
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            $this->invalidateListCache();
            return $turno;
        });
    }

    public function deactivate(Turno $turno): Turno
    {
        return DB::transaction(function () use ($turno) {
            $before = $turno->only(['estado']);

            $turno->estado = RecordStatus::INACTIVO->value;
            $turno->save();

            $this->audit->log(
                'masterdata.admision.turnos.deactivate',
                'Desactivar turno',
                'turno',
                (string)$turno->id,
                [
                    'before' => $before,
                    'after' => $turno->only(['estado']),
                ],
                'success',
                200
            );

            $this->invalidateListCache();
            return $turno;
        });
    }

    private function calcDurationMinutes(string $horaInicio, string $horaFin): array
    {
        $hi = Carbon::createFromFormat('H:i', $horaInicio);
        $hf = Carbon::createFromFormat('H:i', $horaFin);

        if ($hf->lessThanOrEqualTo($hi)) {
            $hf = $hf->copy()->addDay();
        }

        $min = $hi->diffInMinutes($hf);

        if ($min <= 0 || $min > (24 * 60)) {
            throw ValidationException::withMessages(['hora_fin' => ['La hora de fin debe generar una duración válida, mayor a 0 y no superior a 24 horas.']]);
        }

        return [$min, $hi->format('H:i'), $hf->format('H:i')];
    }

    private function buildDescripcion(string $codigo, string $horaInicio, string $horaFin): string
    {
        return "Turno: {$codigo} - de {$horaInicio} a {$horaFin}";
    }
}

