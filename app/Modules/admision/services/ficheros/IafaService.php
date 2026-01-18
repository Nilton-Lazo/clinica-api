<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Iafa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class IafaService
{
    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        if ($n < 1000) {
            return str_pad((string)$n, 3, '0', STR_PAD_LEFT);
        }

        return (string)$n;
    }

    private function nextCodigoInt(): int
    {
        $last = Iafa::query()
            ->select('codigo')
            ->orderByRaw('CAST(codigo AS INTEGER) DESC')
            ->value('codigo');

        $lastInt = $last !== null ? (int)$last : 0;

        return $lastInt + 1;
    }

    public function previewNextCodigo(): string
    {
        return $this->formatCodigo($this->nextCodigoInt());
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;

        $query = Iafa::query();

        if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('razon_social', 'ilike', "%{$q}%")
                    ->orWhere('descripcion_corta', 'ilike', "%{$q}%")
                    ->orWhere('ruc', 'ilike', "%{$q}%");
            });
        }

        return $query
            ->orderByRaw('CAST(codigo AS INTEGER) ASC')
            ->paginate($perPage)
            ->appends([
                'per_page' => $perPage,
                'q' => $q,
                'status' => $status,
            ]);
    }

    public function create(array $data): Iafa
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE iafas IN EXCLUSIVE MODE');

            $codigo = $this->formatCodigo($this->nextCodigoInt());

            $iafa = Iafa::create([
                'codigo' => $codigo,
                'tipo_iafa_id' => $data['tipo_iafa_id'],
                'razon_social' => $data['razon_social'],
                'descripcion_corta' => $data['descripcion_corta'],
                'ruc' => $data['ruc'],
                'direccion' => $data['direccion'] ?? null,
                'representante_legal' => $data['representante_legal'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'pagina_web' => $data['pagina_web'] ?? null,
                'fecha_inicio_cobertura' => $data['fecha_inicio_cobertura'],
                'fecha_fin_cobertura' => $data['fecha_fin_cobertura'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.iafas.create',
                'Crear IAFAS',
                'iafa',
                (string)$iafa->id,
                $iafa->only([
                    'codigo',
                    'tipo_iafa_id',
                    'razon_social',
                    'descripcion_corta',
                    'ruc',
                    'direccion',
                    'representante_legal',
                    'telefono',
                    'pagina_web',
                    'fecha_inicio_cobertura',
                    'fecha_fin_cobertura',
                    'estado',
                ]),
                'success',
                201
            );

            return $iafa;
        });
    }

    public function update(Iafa $iafa, array $data): Iafa
    {
        return DB::transaction(function () use ($iafa, $data) {
            $before = $iafa->only([
                'tipo_iafa_id',
                'razon_social',
                'descripcion_corta',
                'ruc',
                'direccion',
                'representante_legal',
                'telefono',
                'pagina_web',
                'fecha_inicio_cobertura',
                'fecha_fin_cobertura',
                'estado',
            ]);

            $iafa->fill([
                'tipo_iafa_id' => $data['tipo_iafa_id'],
                'razon_social' => $data['razon_social'],
                'descripcion_corta' => $data['descripcion_corta'],
                'ruc' => $data['ruc'],
                'direccion' => $data['direccion'] ?? null,
                'representante_legal' => $data['representante_legal'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'pagina_web' => $data['pagina_web'] ?? null,
                'fecha_inicio_cobertura' => $data['fecha_inicio_cobertura'],
                'fecha_fin_cobertura' => $data['fecha_fin_cobertura'],
                'estado' => $data['estado'],
            ]);
            $iafa->save();

            $after = $iafa->only([
                'tipo_iafa_id',
                'razon_social',
                'descripcion_corta',
                'ruc',
                'direccion',
                'representante_legal',
                'telefono',
                'pagina_web',
                'fecha_inicio_cobertura',
                'fecha_fin_cobertura',
                'estado',
            ]);

            $this->audit->log(
                'masterdata.admision.iafas.update',
                'Actualizar IAFAS',
                'iafa',
                (string)$iafa->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            return $iafa;
        });
    }

    public function deactivate(Iafa $iafa): Iafa
    {
        return DB::transaction(function () use ($iafa) {
            $before = $iafa->only(['estado']);

            $iafa->estado = RecordStatus::INACTIVO->value;
            $iafa->save();

            $this->audit->log(
                'masterdata.admision.iafas.deactivate',
                'Desactivar IAFAS',
                'iafa',
                (string)$iafa->id,
                ['before' => $before, 'after' => $iafa->only(['estado'])],
                'success',
                200
            );

            return $iafa;
        });
    }
}
