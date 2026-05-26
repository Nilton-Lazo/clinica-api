<?php

namespace App\Modules\ficheros\queries;

use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Paquete;
use App\Modules\admision\models\Tarifa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PaquetesPorTarifaQuery
{
    use AppliesListingQuery;

    public function paginate(Tarifa $tarifa, GridParams $params): LengthAwarePaginator
    {
        $query = Paquete::query()
            ->select(['id', 'codigo', 'descripcion', 'tarifa_id', 'estado', 'precio_sin_igv'])
            ->where('tarifa_id', (int) $tarifa->id)
            ->where('estado', RecordStatus::ACTIVO->value)
            ->when($params->q !== null, function (Builder $query) use ($params) {
                $this->applyListingSearch($query, $params, ['codigo', 'descripcion']);
            });

        $this->applyListingSort(
            $query,
            $params,
            ['codigo', 'descripcion', 'precio_sin_igv'],
            'codigo'
        );

        return $query->paginate($params->perPage, ['*'], 'page', $params->page);
    }
}
