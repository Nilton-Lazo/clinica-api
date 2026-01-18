<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\TipoCliente;
use App\Modules\admision\requests\ficheros\TipoClienteStoreRequest;
use App\Modules\admision\requests\ficheros\TipoClienteUpdateRequest;
use App\Modules\admision\services\ficheros\TipoClienteService;
use Illuminate\Http\Request;

class TipoClienteController extends Controller
{
    public function __construct(private TipoClienteService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', TipoCliente::class);

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
        $this->authorize('create', TipoCliente::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(TipoClienteStoreRequest $request)
    {
        $this->authorize('create', TipoCliente::class);

        $tipoCliente = $this->service->create($request->validated());

        return response()->json(['data' => $tipoCliente], 201);
    }

    public function update(TipoClienteUpdateRequest $request, TipoCliente $tipoCliente)
    {
        $this->authorize('update', $tipoCliente);

        $updated = $this->service->update($tipoCliente, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(TipoCliente $tipoCliente)
    {
        $this->authorize('deactivate', $tipoCliente);

        $updated = $this->service->deactivate($tipoCliente);

        return response()->json(['data' => $updated]);
    }
}
