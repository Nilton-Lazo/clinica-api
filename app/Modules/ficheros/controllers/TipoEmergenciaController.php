<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\TipoEmergencia;
use App\Modules\ficheros\requests\TipoEmergenciaStoreRequest;
use App\Modules\ficheros\requests\TipoEmergenciaUpdateRequest;
use App\Modules\ficheros\services\TipoEmergenciaService;
use Illuminate\Http\Request;

class TipoEmergenciaController extends Controller
{
    public function __construct(private TipoEmergenciaService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', TipoEmergencia::class);

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
        $this->authorize('create', TipoEmergencia::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(TipoEmergenciaStoreRequest $request)
    {
        $this->authorize('create', TipoEmergencia::class);

        $tipoEmergencia = $this->service->create($request->validated());

        return response()->json(['data' => $tipoEmergencia], 201);
    }

    public function update(TipoEmergenciaUpdateRequest $request, TipoEmergencia $tipoEmergencia)
    {
        $this->authorize('update', $tipoEmergencia);

        $updated = $this->service->update($tipoEmergencia, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(TipoEmergencia $tipoEmergencia)
    {
        $this->authorize('deactivate', $tipoEmergencia);

        $updated = $this->service->deactivate($tipoEmergencia);

        return response()->json(['data' => $updated]);
    }
}
