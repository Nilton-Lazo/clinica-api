<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Iafa;
use App\Modules\admision\models\Tarifa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TarifaService
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
        $last = Tarifa::query()
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

        $query = Tarifa::query();

        if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('descripcion_tarifa', 'ilike', "%{$q}%");
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

    private function enforceParticularRule(?int $iafaId, bool $requiereAcreditacion): bool
    {
        if ($iafaId === null) {
            return $requiereAcreditacion;
        }

        $iafa = Iafa::query()->with(['tipo'])->findOrFail($iafaId);
        $tipoDesc = $iafa->tipo ? trim((string)$iafa->tipo->descripcion) : '';

        if (strcasecmp($tipoDesc, 'Particular') === 0) {
            return false;
        }

        return $requiereAcreditacion;
    }

    private function switchBaseTo(Tarifa $target): array
    {
        if ($target->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['tarifa_base' => ['Solo una tarifa ACTIVA puede ser tarifario base.']]);
        }

        $previous = Tarifa::query()->where('tarifa_base', true)->first();
        $previousId = $previous ? (int)$previous->id : null;

        if ($previous && (int)$previous->id !== (int)$target->id) {
            $previous->tarifa_base = false;
            $previous->save();
        }

        if (!$target->tarifa_base) {
            $target->tarifa_base = true;
            $target->save();
        }

        return ['previous_base_id' => $previousId];
    }

    public function create(array $data): Tarifa
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE tarifas IN EXCLUSIVE MODE');

            $codigo = $this->formatCodigo($this->nextCodigoInt());

            $iafaId = $data['iafa_id'] ?? null;
            $requiereAcreditacion = $this->enforceParticularRule($iafaId, (bool)$data['requiere_acreditacion']);
            $makeBase = (bool)$data['tarifa_base'];

            if ($makeBase) {
                Tarifa::query()->where('tarifa_base', true)->update(['tarifa_base' => false, 'updated_at' => now()]);
            }

            $tarifa = Tarifa::create([
                'codigo' => $codigo,
                'requiere_acreditacion' => $requiereAcreditacion,
                'tarifa_base' => $makeBase,
                'descripcion_tarifa' => $data['descripcion_tarifa'],
                'iafa_id' => $iafaId,

                'factor_clinica' => $data['factor_clinica'] ?? 1.00,
                'factor_laboratorio' => $data['factor_laboratorio'] ?? 1.00,
                'factor_ecografia' => $data['factor_ecografia'] ?? 1.00,
                'factor_procedimientos' => $data['factor_procedimientos'] ?? 1.00,
                'factor_rayos_x' => $data['factor_rayos_x'] ?? 1.00,
                'factor_tomografia' => $data['factor_tomografia'] ?? 1.00,
                'factor_patologia' => $data['factor_patologia'] ?? 1.00,
                'factor_medicina_fisica' => $data['factor_medicina_fisica'] ?? 1.00,
                'factor_resonancia' => $data['factor_resonancia'] ?? 1.00,
                'factor_honorarios_medicos' => $data['factor_honorarios_medicos'] ?? 1.00,
                'factor_medicinas' => $data['factor_medicinas'] ?? 1.00,
                'factor_equipos_oxigeno' => $data['factor_equipos_oxigeno'] ?? 1.00,
                'factor_banco_sangre' => $data['factor_banco_sangre'] ?? 1.00,
                'factor_mamografia' => $data['factor_mamografia'] ?? 1.00,
                'factor_densitometria' => $data['factor_densitometria'] ?? 1.00,
                'factor_psicoprofilaxis' => $data['factor_psicoprofilaxis'] ?? 1.00,
                'factor_otros_servicios' => $data['factor_otros_servicios'] ?? 1.00,
                'factor_medicamentos_comerciales' => $data['factor_medicamentos_comerciales'] ?? 1.00,
                'factor_medicamentos_genericos' => $data['factor_medicamentos_genericos'] ?? 1.00,
                'factor_material_medico' => $data['factor_material_medico'] ?? 1.00,

                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.tarifas.create',
                'Crear tarifa',
                'tarifa',
                (string)$tarifa->id,
                $tarifa->only([
                    'codigo',
                    'requiere_acreditacion',
                    'tarifa_base',
                    'descripcion_tarifa',
                    'iafa_id',
                    'factor_clinica',
                    'factor_laboratorio',
                    'factor_ecografia',
                    'factor_procedimientos',
                    'factor_rayos_x',
                    'factor_tomografia',
                    'factor_patologia',
                    'factor_medicina_fisica',
                    'factor_resonancia',
                    'factor_honorarios_medicos',
                    'factor_medicinas',
                    'factor_equipos_oxigeno',
                    'factor_banco_sangre',
                    'factor_mamografia',
                    'factor_densitometria',
                    'factor_psicoprofilaxis',
                    'factor_otros_servicios',
                    'factor_medicamentos_comerciales',
                    'factor_medicamentos_genericos',
                    'factor_material_medico',
                    'fecha_creacion',
                    'estado',
                ]),
                'success',
                201
            );

            return $tarifa;
        });
    }

    public function update(Tarifa $tarifa, array $data): Tarifa
    {
        return DB::transaction(function () use ($tarifa, $data) {
            DB::statement('LOCK TABLE tarifas IN EXCLUSIVE MODE');

            $before = $tarifa->only([
                'requiere_acreditacion',
                'tarifa_base',
                'descripcion_tarifa',
                'iafa_id',
                'factor_clinica',
                'factor_laboratorio',
                'factor_ecografia',
                'factor_procedimientos',
                'factor_rayos_x',
                'factor_tomografia',
                'factor_patologia',
                'factor_medicina_fisica',
                'factor_resonancia',
                'factor_honorarios_medicos',
                'factor_medicinas',
                'factor_equipos_oxigeno',
                'factor_banco_sangre',
                'factor_mamografia',
                'factor_densitometria',
                'factor_psicoprofilaxis',
                'factor_otros_servicios',
                'factor_medicamentos_comerciales',
                'factor_medicamentos_genericos',
                'factor_material_medico',
                'estado',
            ]);

            $requestedBase = (bool)$data['tarifa_base'];

            if ($tarifa->tarifa_base && !$requestedBase) {
                throw ValidationException::withMessages([
                    'tarifa_base' => ['No se puede desmarcar el tarifario base directamente. Usa "Marcar como base" en otra tarifa.'],
                ]);
            }

            if (!$tarifa->tarifa_base && $requestedBase) {
                $this->switchBaseTo($tarifa);
            } else {
                if ($tarifa->estado !== (string)$data['estado'] && $tarifa->tarifa_base && strtoupper(trim((string)$data['estado'])) !== RecordStatus::ACTIVO->value) {
                    throw ValidationException::withMessages(['estado' => ['No se puede inactivar/suspender el tarifario base. Primero marca otra tarifa como base.']]);
                }
            }

            $iafaId = $data['iafa_id'] ?? null;
            $requiereAcreditacion = $this->enforceParticularRule($iafaId, (bool)$data['requiere_acreditacion']);

            $tarifa->fill([
                'requiere_acreditacion' => $requiereAcreditacion,
                'tarifa_base' => $tarifa->tarifa_base,
                'descripcion_tarifa' => $data['descripcion_tarifa'],
                'iafa_id' => $iafaId,

                'factor_clinica' => $data['factor_clinica'] ?? $tarifa->factor_clinica,
                'factor_laboratorio' => $data['factor_laboratorio'] ?? $tarifa->factor_laboratorio,
                'factor_ecografia' => $data['factor_ecografia'] ?? $tarifa->factor_ecografia,
                'factor_procedimientos' => $data['factor_procedimientos'] ?? $tarifa->factor_procedimientos,
                'factor_rayos_x' => $data['factor_rayos_x'] ?? $tarifa->factor_rayos_x,
                'factor_tomografia' => $data['factor_tomografia'] ?? $tarifa->factor_tomografia,
                'factor_patologia' => $data['factor_patologia'] ?? $tarifa->factor_patologia,
                'factor_medicina_fisica' => $data['factor_medicina_fisica'] ?? $tarifa->factor_medicina_fisica,
                'factor_resonancia' => $data['factor_resonancia'] ?? $tarifa->factor_resonancia,
                'factor_honorarios_medicos' => $data['factor_honorarios_medicos'] ?? $tarifa->factor_honorarios_medicos,
                'factor_medicinas' => $data['factor_medicinas'] ?? $tarifa->factor_medicinas,
                'factor_equipos_oxigeno' => $data['factor_equipos_oxigeno'] ?? $tarifa->factor_equipos_oxigeno,
                'factor_banco_sangre' => $data['factor_banco_sangre'] ?? $tarifa->factor_banco_sangre,
                'factor_mamografia' => $data['factor_mamografia'] ?? $tarifa->factor_mamografia,
                'factor_densitometria' => $data['factor_densitometria'] ?? $tarifa->factor_densitometria,
                'factor_psicoprofilaxis' => $data['factor_psicoprofilaxis'] ?? $tarifa->factor_psicoprofilaxis,
                'factor_otros_servicios' => $data['factor_otros_servicios'] ?? $tarifa->factor_otros_servicios,
                'factor_medicamentos_comerciales' => $data['factor_medicamentos_comerciales'] ?? $tarifa->factor_medicamentos_comerciales,
                'factor_medicamentos_genericos' => $data['factor_medicamentos_genericos'] ?? $tarifa->factor_medicamentos_genericos,
                'factor_material_medico' => $data['factor_material_medico'] ?? $tarifa->factor_material_medico,

                'estado' => strtoupper(trim((string)$data['estado'])),
            ]);

            $tarifa->save();

            $after = $tarifa->only([
                'requiere_acreditacion',
                'tarifa_base',
                'descripcion_tarifa',
                'iafa_id',
                'factor_clinica',
                'factor_laboratorio',
                'factor_ecografia',
                'factor_procedimientos',
                'factor_rayos_x',
                'factor_tomografia',
                'factor_patologia',
                'factor_medicina_fisica',
                'factor_resonancia',
                'factor_honorarios_medicos',
                'factor_medicinas',
                'factor_equipos_oxigeno',
                'factor_banco_sangre',
                'factor_mamografia',
                'factor_densitometria',
                'factor_psicoprofilaxis',
                'factor_otros_servicios',
                'factor_medicamentos_comerciales',
                'factor_medicamentos_genericos',
                'factor_material_medico',
                'estado',
            ]);

            $this->audit->log(
                'masterdata.admision.tarifas.update',
                'Actualizar tarifa',
                'tarifa',
                (string)$tarifa->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            return $tarifa;
        });
    }

    public function setBase(Tarifa $tarifa): Tarifa
    {
        return DB::transaction(function () use ($tarifa) {
            DB::statement('LOCK TABLE tarifas IN EXCLUSIVE MODE');

            $before = $tarifa->only(['tarifa_base', 'estado']);

            $meta = $this->switchBaseTo($tarifa);

            if ($tarifa->iafa_id !== null) {
                $tarifa->requiere_acreditacion = $this->enforceParticularRule($tarifa->iafa_id, (bool)$tarifa->requiere_acreditacion);
                $tarifa->save();
            }

            $this->audit->log(
                'masterdata.admision.tarifas.set_base',
                'Marcar tarifario base',
                'tarifa',
                (string)$tarifa->id,
                [
                    'before' => $before,
                    'after' => $tarifa->only(['tarifa_base', 'estado']),
                    'previous_base_id' => $meta['previous_base_id'],
                ],
                'success',
                200
            );

            return $tarifa;
        });
    }

    public function deactivate(Tarifa $tarifa): Tarifa
    {
        return DB::transaction(function () use ($tarifa) {
            if ($tarifa->tarifa_base) {
                throw ValidationException::withMessages(['estado' => ['No se puede desactivar el tarifario base. Primero marca otra tarifa como base.']]);
            }

            $before = $tarifa->only(['estado']);

            $tarifa->estado = RecordStatus::INACTIVO->value;
            $tarifa->save();

            $this->audit->log(
                'masterdata.admision.tarifas.deactivate',
                'Desactivar tarifa',
                'tarifa',
                (string)$tarifa->id,
                ['before' => $before, 'after' => $tarifa->only(['estado'])],
                'success',
                200
            );

            return $tarifa;
        });
    }
}
