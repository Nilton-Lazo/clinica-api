<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Core\support\TipoProfesionalClinica;
use App\Modules\admision\models\Medico;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MedicoService
{
    public function __construct(private AuditService $audit) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;

        $query = Medico::query()->with(['especialidad:id,codigo,descripcion']);

        if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('dni', 'ilike', "%{$q}%")
                    ->orWhere('cmp', 'ilike', "%{$q}%")
                    ->orWhere('rne', 'ilike', "%{$q}%")
                    ->orWhere('ruc', 'ilike', "%{$q}%")
                    ->orWhere('nombres', 'ilike', "%{$q}%")
                    ->orWhere('apellido_paterno', 'ilike', "%{$q}%")
                    ->orWhere('apellido_materno', 'ilike', "%{$q}%")
                    ->orWhere('email', 'ilike', "%{$q}%");
            });
        }

        return $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->paginate($perPage)
            ->appends([
                'per_page' => $perPage,
                'q' => $q,
                'status' => $status,
            ]);
    }

    public function create(array $data): Medico
    {
        return DB::transaction(function () use ($data) {
            $medico = Medico::create([
                'codigo' => $data['codigo'],

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
                'codigo' => $data['codigo'],

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

            return $medico->load(['especialidad:id,codigo,descripcion']);
        });
    }
}
