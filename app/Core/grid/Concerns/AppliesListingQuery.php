<?php

namespace App\Core\grid\Concerns;

use App\Core\grid\GridParams;
use App\Core\support\CodigoCorrelativo;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;

trait AppliesListingQuery
{
    protected function applyListingSearch(Builder $query, GridParams $params, array $columns): Builder
    {
        if ($params->q === null) {
            return $query;
        }

        $term = $params->q;

        return $query->where(function (Builder $sub) use ($columns, $term) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $sub->where($column, 'ilike', "%{$term}%");
                    continue;
                }
                $sub->orWhere($column, 'ilike', "%{$term}%");
            }
        });
    }

    protected function applyListingStatus(Builder $query, GridParams $params, string $column = 'estado'): Builder
    {
        $status = $params->status();
        if ($status === null || $status === '') {
            return $query;
        }

        if (! in_array($status, RecordStatus::values(), true)) {
            return $query;
        }

        return $query->where($column, $status);
    }

    protected function applyListingSort(
        Builder $query,
        GridParams $params,
        array $allowedSorts,
        string $defaultSort,
        array $sortMap = [],
    ): Builder {
        $sort = $params->sort ?? $defaultSort;
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = $defaultSort;
        }

        $column = $sortMap[$sort] ?? $sort;
        $direction = $params->sortDir === 'desc' ? 'desc' : 'asc';

        if ($column === 'codigo') {
            $qualified = $query->getModel()->qualifyColumn('codigo');
            $driver = $query->getConnection()->getDriverName();

            if ($driver === 'pgsql') {
                if ($direction === 'desc') {
                    return $query->orderByRaw(
                        "(CASE WHEN {$qualified} ~ '^[0-9]+\$' THEN {$qualified}::bigint END) DESC NULLS LAST, {$qualified} DESC"
                    );
                }

                return CodigoCorrelativo::orderByCodigoAsc($query, $qualified);
            }

            if ($direction === 'desc') {
                return $query->orderByRaw(
                    "(CASE WHEN {$qualified} REGEXP '^[0-9]+\$' THEN CAST({$qualified} AS UNSIGNED) END) DESC, {$qualified} DESC"
                );
            }

            return CodigoCorrelativo::orderByCodigoAsc($query, $qualified);
        }

        return $query->orderBy($column, $direction);
    }
}
