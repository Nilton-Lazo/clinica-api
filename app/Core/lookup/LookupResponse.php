<?php

namespace App\Core\lookup;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class LookupResponse
{
    public static function fromPaginator(LengthAwarePaginator $paginator, ?callable $transformer = null): JsonResponse
    {
        $items = $paginator->items();

        if ($transformer !== null) {
            $items = array_map($transformer, $items);
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->currentPage() < $paginator->lastPage(),
            ],
        ]);
    }
}
