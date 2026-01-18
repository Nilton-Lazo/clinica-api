<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Contratante;
use App\Modules\admision\requests\ficheros\ContratanteStoreRequest;
use App\Modules\admision\requests\ficheros\ContratanteUpdateRequest;
use App\Modules\admision\services\ficheros\ContratanteService;
use Illuminate\Http\Request;

class ContratanteController extends Controller
{
    public function __construct(private ContratanteService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Contratante::class);

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
        $this->authorize('create', Contratante::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(ContratanteStoreRequest $request)
    {
        $this->authorize('create', Contratante::class);

        $contratante = $this->service->create($request->validated());

        return response()->json(['data' => $contratante], 201);
    }

    public function update(ContratanteUpdateRequest $request, Contratante $contratante)
    {
        $this->authorize('update', $contratante);

        $updated = $this->service->update($contratante, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Contratante $contratante)
    {
        $this->authorize('deactivate', $contratante);

        $updated = $this->service->deactivate($contratante);

        return response()->json(['data' => $updated]);
    }
}
