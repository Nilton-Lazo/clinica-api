<?php

namespace App\Core\grid;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class GridResponse
{
    public static function fromPaginator(LengthAwarePaginator $paginator, ?callable $transformer = null, array $extraMeta = []): JsonResponse
    {
        $items = $paginator->items();

        if ($transformer !== null) {
            $items = array_map($transformer, $items);
        }

        return response()->json([
            'data' => $items,
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ], $extraMeta),
        ]);
    }
}
