<?php

namespace App\Modules\ficheros\services;

use App\Core\support\RecordStatus;
use App\Core\grid\GridParams;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaRecargoNoche;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TarifaRecargoNocheService
{
    public function paginate(Tarifa $tarifa, GridParams $params): LengthAwarePaginator
    {
        $query = TarifaRecargoNoche::query()
            ->select('tarifa_recargo_noche.*')
            ->leftJoin('tarifa_categorias as tc', 'tc.id', '=', 'tarifa_recargo_noche.tarifa_categoria_id')
            ->where('tarifa_recargo_noche.tarifa_id', (int)$tarifa->id)
            ->with('tarifaCategoria:id,tarifa_id,codigo,nombre');

        $status = $params->status();
        if ($status !== null && in_array($status, RecordStatus::values(), true)) {
            $query->where('tarifa_recargo_noche.estado', $status);
        }

        if ($params->q !== null) {
            $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $term = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $params->q) . '%';
            $query->where(function ($sub) use ($operator, $term) {
                $sub->where('tc.codigo', $operator, $term)
                    ->orWhere('tc.nombre', $operator, $term);
            });
        }

        $sortMap = [
            'codigo' => 'tc.codigo',
            'categoria' => 'tc.nombre',
            'porcentaje' => 'tarifa_recargo_noche.porcentaje',
            'hora_desde' => 'tarifa_recargo_noche.hora_desde',
            'hora_hasta' => 'tarifa_recargo_noche.hora_hasta',
            'estado' => 'tarifa_recargo_noche.estado',
        ];
        $sort = $params->sort ?? 'codigo';
        $column = $sortMap[$sort] ?? $sortMap['codigo'];
        $direction = $params->sortDir === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($column, $direction)
            ->orderBy('tarifa_recargo_noche.id')
            ->paginate($params->perPage, ['tarifa_recargo_noche.*'], 'page', $params->page);
    }

    public function activeCategoriaIds(Tarifa $tarifa): array
    {
        return TarifaRecargoNoche::query()
            ->where('tarifa_id', (int)$tarifa->id)
            ->where('estado', RecordStatus::ACTIVO->value)
            ->pluck('tarifa_categoria_id')
            ->map(fn ($id) => (int)$id)
            ->values()
            ->all();
    }

    public function listByTarifa(Tarifa $tarifa, ?string $status = null): Collection
    {
        $query = TarifaRecargoNoche::query()
            ->where('tarifa_id', (int)$tarifa->id)
            ->with('tarifaCategoria:id,tarifa_id,codigo,nombre');

        if ($status !== null && $status !== '' && $status !== 'ALL') {
            $query->where('estado', $status);
        }

        return $query->orderBy('tarifa_categoria_id')->get();
    }

    public function create(Tarifa $tarifa, array $data): TarifaRecargoNoche
    {
        $this->assertTarifaOperativa($tarifa);
        $this->assertCategoriaPerteneceATarifa($tarifa, (int)$data['tarifa_categoria_id']);

        $exists = TarifaRecargoNoche::query()
            ->where('tarifa_id', (int)$tarifa->id)
            ->where('tarifa_categoria_id', (int)$data['tarifa_categoria_id'])
            ->where('estado', RecordStatus::ACTIVO->value)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'tarifa_categoria_id' => ['Ya existe una regla de recargo nocturno para esta categoría en este tarifario.'],
            ]);
        }

        $horaDesde = $data['hora_desde'] ?? '19:00';
        $horaHasta = $data['hora_hasta'] ?? $this->horaDesdeMas12($horaDesde);

        $recargo = TarifaRecargoNoche::create([
            'tarifa_id' => $tarifa->id,
            'tarifa_categoria_id' => (int)$data['tarifa_categoria_id'],
            'porcentaje' => (float)$data['porcentaje'],
            'hora_desde' => $horaDesde,
            'hora_hasta' => $horaHasta,
            'estado' => isset($data['estado']) && in_array($data['estado'], RecordStatus::values(), true)
                ? $data['estado']
                : RecordStatus::ACTIVO->value,
        ]);

        TarifarioCatalogoService::invalidateServiciosCacheForTarifa((int)$tarifa->id);

        return $recargo;
    }

    public function update(TarifaRecargoNoche $recargo, array $data): TarifaRecargoNoche
    {
        if (isset($data['porcentaje'])) {
            $recargo->porcentaje = (float)$data['porcentaje'];
        }
        if (isset($data['hora_desde'])) {
            $recargo->hora_desde = $data['hora_desde'];
        }
        if (isset($data['hora_hasta'])) {
            $recargo->hora_hasta = $data['hora_hasta'];
        }
        if (isset($data['estado']) && in_array($data['estado'], RecordStatus::values(), true)) {
            $recargo->estado = $data['estado'];
        }
        $recargo->save();

        TarifarioCatalogoService::invalidateServiciosCacheForTarifa((int)$recargo->tarifa_id);

        return $recargo->fresh(['tarifaCategoria']);
    }

    public function deactivate(TarifaRecargoNoche $recargo): TarifaRecargoNoche
    {
        $recargo->estado = RecordStatus::INACTIVO->value;
        $recargo->save();

        TarifarioCatalogoService::invalidateServiciosCacheForTarifa((int)$recargo->tarifa_id);

        return $recargo->fresh(['tarifaCategoria']);
    }

    private function assertTarifaOperativa(Tarifa $tarifa): void
    {
        if ($tarifa->tarifa_base) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['No se pueden configurar recargos en el tarifario base.'],
            ]);
        }
        if ($tarifa->estado !== \App\Core\support\RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['El tarifario seleccionado debe estar activo para configurar recargo nocturno.'],
            ]);
        }
    }

    private function assertCategoriaPerteneceATarifa(Tarifa $tarifa, int $categoriaId): void
    {
        $ok = \App\Modules\admision\models\TarifaCategoria::query()
            ->where('id', $categoriaId)
            ->where('tarifa_id', (int)$tarifa->id)
            ->exists();

        if (!$ok) {
            throw ValidationException::withMessages([
                'tarifa_categoria_id' => ['La categoría no pertenece a este tarifario.'],
            ]);
        }
    }

    private function horaDesdeMas12(string $hora): string
    {
        $parts = explode(':', $hora);
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
        $h = ($h + 12) % 24;
        return sprintf('%02d:%02d', $h, $m);
    }
}

