<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\AreaJefatura;
use App\Modules\ficheros\requests\AreaJefaturaStoreRequest;
use App\Modules\ficheros\requests\AreaJefaturaUpdateRequest;
use App\Modules\ficheros\services\AreaJefaturaService;
use Illuminate\Http\Request;

class AreaJefaturaController extends Controller
{
    public function __construct(private AreaJefaturaService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', AreaJefatura::class);

        $p = $this->service->paginate($request->only(['q', 'status', 'per_page', 'page']));

        return response()->json([
            'data' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }

    public function nextCodigo()
    {
        $this->authorize('create', AreaJefatura::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(AreaJefaturaStoreRequest $request)
    {
        $this->authorize('create', AreaJefatura::class);

        $areaJefatura = $this->service->create($request->validated());

        return response()->json(['data' => $areaJefatura], 201);
    }

    public function update(AreaJefaturaUpdateRequest $request, AreaJefatura $areaJefatura)
    {
        $this->authorize('update', $areaJefatura);

        $updated = $this->service->update($areaJefatura, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(AreaJefatura $areaJefatura)
    {
        $this->authorize('deactivate', $areaJefatura);

        $updated = $this->service->deactivate($areaJefatura);

        return response()->json(['data' => $updated]);
    }
}
