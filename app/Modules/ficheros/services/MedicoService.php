<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Core\support\TipoProfesionalClinica;
use App\Modules\admision\models\Medico;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MedicoService
{
    use AppliesListingQuery;

    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        return CodigoCorrelativo::format($n);
    }

    private function nextCodigo(): string
    {
        $row = DB::selectOne("SELECT nextval('medicos_codigo_seq') AS n");
        $n = (int)($row->n ?? 1);
        if ($n <= 0) $n = 1;

        return $this->formatCodigo($n);
    }

    public function previewNextCodigo(): string
    {
        $row = DB::selectOne("SELECT last_value, is_called FROM medicos_codigo_seq");
        $last = (int)($row->last_value ?? 0);
        $isCalled = (bool)($row->is_called ?? true);

        $next = $isCalled ? ($last + 1) : $last;
        if ($next <= 0) $next = 1;

        return $this->formatCodigo($next);
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'ficheros:medicos:version';

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
        $cacheKey = 'ficheros:medicos:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = Medico::query()->with(['especialidad:id,codigo,descripcion']);
            $this->applyListingStatus($query, $params);

            if ($params->q !== null) {
                $term = $params->q;
                $query->where(function ($sub) use ($term) {
                    $sub->where('codigo', 'ilike', "%{$term}%")
                        ->orWhere('dni', 'ilike', "%{$term}%")
                        ->orWhere('cmp', 'ilike', "%{$term}%")
                        ->orWhere('rne', 'ilike', "%{$term}%")
                        ->orWhere('ruc', 'ilike', "%{$term}%")
                        ->orWhere('nombres', 'ilike', "%{$term}%")
                        ->orWhere('apellido_paterno', 'ilike', "%{$term}%")
                        ->orWhere('apellido_materno', 'ilike', "%{$term}%")
                        ->orWhere('email', 'ilike', "%{$term}%");
                });
            }

            $this->applyListingSort($query, $params, ['codigo', 'apellido_paterno', 'apellido_materno', 'nombres', 'estado'], 'apellido_paterno');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): Medico
    {
        return DB::transaction(function () use ($data) {
            $medico = Medico::create([
                'codigo' => $this->nextCodigo(),

                'cmp' => $data['cmp'] ?? null,
                'rne' => $data['rne'] ?? null,
                'dni' => $data['dni'] ?? null,

                'tipo_profesional_clinica' => $data['tipo_profesional_clinica'] ?? TipoProfesionalClinica::STAFF->value,

                'nombres' => $data['nombres'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'],

                'direccion' => $data['direccion'] ?? null,
                'centro_trabajo' => $data['centro_trabajo'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,

                'ruc' => $data['ruc'] ?? null,

                'especialidad_id' => $data['especialidad_id'],

                'telefono' => $data['telefono'] ?? null,
                'telefono_02' => $data['telefono_02'] ?? null,
                'email' => $data['email'] ?? null,

                'adicionales' => (int)($data['adicionales'] ?? 0),
                'extras' => (int)($data['extras'] ?? 0),
                'tiempo_promedio_por_paciente' => (int)($data['tiempo_promedio_por_paciente'] ?? 0),

                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.medicos.create',
                'Crear médico',
                'medico',
                (string)$medico->id,
                $medico->only([
                    'codigo',
                    'cmp',
                    'rne',
                    'dni',
                    'tipo_profesional_clinica',
                    'nombres',
                    'apellido_paterno',
                    'apellido_materno',
                    'direccion',
                    'centro_trabajo',
                    'fecha_nacimiento',
                    'ruc',
                    'especialidad_id',
                    'telefono',
                    'telefono_02',
                    'email',
                    'adicionales',
                    'extras',
                    'tiempo_promedio_por_paciente',
                    'estado',
                ]),
                'success',
                201
            );

            $this->invalidateListCache();
            return $medico->load(['especialidad:id,codigo,descripcion']);
        });
    }

    public function update(Medico $medico, array $data): Medico
    {
        return DB::transaction(function () use ($medico, $data) {
            $before = $medico->only([
                'codigo',
                'cmp',
                'rne',
                'dni',
                'tipo_profesional_clinica',
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'direccion',
                'centro_trabajo',
                'fecha_nacimiento',
                'ruc',
                'especialidad_id',
                'telefono',
                'telefono_02',
                'email',
                'adicionales',
                'extras',
                'tiempo_promedio_por_paciente',
                'estado',
            ]);

            $medico->fill([
                'cmp' => $data['cmp'] ?? null,
                'rne' => $data['rne'] ?? null,
                'dni' => $data['dni'] ?? null,

                'tipo_profesional_clinica' => $data['tipo_profesional_clinica'],

                'nombres' => $data['nombres'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'],

                'direccion' => $data['direccion'] ?? null,
                'centro_trabajo' => $data['centro_trabajo'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,

                'ruc' => $data['ruc'] ?? null,

                'especialidad_id' => $data['especialidad_id'],

                'telefono' => $data['telefono'] ?? null,
                'telefono_02' => $data['telefono_02'] ?? null,
                'email' => $data['email'] ?? null,

                'adicionales' => (int)$data['adicionales'],
                'extras' => (int)$data['extras'],
                'tiempo_promedio_por_paciente' => (int)$data['tiempo_promedio_por_paciente'],

                'estado' => $data['estado'],
            ]);

            $medico->save();

            $after = $medico->only([
                'codigo',
                'cmp',
                'rne',
                'dni',
                'tipo_profesional_clinica',
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'direccion',
                'centro_trabajo',
                'fecha_nacimiento',
                'ruc',
                'especialidad_id',
                'telefono',
                'telefono_02',
                'email',
                'adicionales',
                'extras',
                'tiempo_promedio_por_paciente',
                'estado',
            ]);

            $this->audit->log(
                'masterdata.admision.medicos.update',
                'Actualizar médico',
                'medico',
                (string)$medico->id,
                [
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            $this->invalidateListCache();
            return $medico->load(['especialidad:id,codigo,descripcion']);
        });
    }

    public function deactivate(Medico $medico): Medico
    {
        return DB::transaction(function () use ($medico) {
            $before = $medico->only(['estado']);

            $medico->estado = RecordStatus::INACTIVO->value;
            $medico->save();

            $this->audit->log(
                'masterdata.admision.medicos.deactivate',
                'Desactivar médico',
                'medico',
                (string)$medico->id,
                [
                    'before' => $before,
                    'after' => $medico->only(['estado']),
                ],
                'success',
                200
            );

            $this->invalidateListCache();
            return $medico->load(['especialidad:id,codigo,descripcion']);
        });
    }
}

