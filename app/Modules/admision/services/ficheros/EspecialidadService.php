<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Especialidad;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EspecialidadService
{
    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        $codigo = str_pad((string)$n, 3, '0', STR_PAD_LEFT);

        if (strlen($codigo) > 10) {
            throw new \RuntimeException('No se pudo generar el código: excede 10 caracteres.');
        }

        return $codigo;
    }

    private function nextCodigo(): string
    {
        $row = DB::selectOne("SELECT nextval('especialidades_codigo_seq') AS n");
        $n = (int)($row->n ?? 0);

        if ($n <= 0) {
            throw new \RuntimeException('No se pudo generar el código de especialidad.');
        }

        return $this->formatCodigo($n);
    }

    public function peekNextCodigo(): string
    {
        $last = Especialidad::query()
            ->select('codigo')
            ->whereRaw("codigo ~ '^[0-9]+$'")
            ->orderByRaw("codigo::int desc")
            ->first();

        $n = 0;
        if ($last && is_string($last->codigo) && $last->codigo !== '') {
            $n = (int)$last->codigo;
        }

        return $this->formatCodigo($n + 1);
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;

        $query = Especialidad::query();

        if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('descripcion', 'ilike', "%{$q}%");
            });
        }

        return $query->orderBy('codigo')->paginate($perPage)->appends([
            'per_page' => $perPage,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function create(array $data): Especialidad
    {
        return DB::transaction(function () use ($data) {
            $especialidad = Especialidad::create([
                'codigo' => $this->nextCodigo(),
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.especialidades.create',
                'Crear especialidad',
                'especialidad',
                (string)$especialidad->id,
                [
                    'codigo' => $especialidad->codigo,
                    'descripcion' => $especialidad->descripcion,
                    'estado' => $especialidad->estado,
                ],
                'success',
                201
            );

            return $especialidad;
        });
    }

    public function update(Especialidad $especialidad, array $data): Especialidad
    {
        return DB::transaction(function () use ($especialidad, $data) {
            $before = $especialidad->only(['codigo', 'descripcion', 'estado']);

            $especialidad->fill([
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);

            $especialidad->save();

            $after = $especialidad->only(['codigo', 'descripcion', 'estado']);

            $this->audit->log(
                'masterdata.admision.especialidades.update',
                'Actualizar especialidad',
                'especialidad',
                (string)$especialidad->id,
                [
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            return $especialidad;
        });
    }

    public function deactivate(Especialidad $especialidad): Especialidad
    {
        return DB::transaction(function () use ($especialidad) {
            $before = $especialidad->only(['estado']);

            $especialidad->estado = RecordStatus::INACTIVO->value;
            $especialidad->save();

            $this->audit->log(
                'masterdata.admision.especialidades.deactivate',
                'Desactivar especialidad',
                'especialidad',
                (string)$especialidad->id,
                [
                    'before' => $before,
                    'after' => $especialidad->only(['estado']),
                ],
                'success',
                200
            );

            return $especialidad;
        });
    }
}
