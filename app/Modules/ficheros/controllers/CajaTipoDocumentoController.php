<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\CajaTipoDocumento;
use App\Modules\ficheros\requests\CajaTipoDocumentoStoreRequest;
use App\Modules\ficheros\requests\CajaTipoDocumentoUpdateRequest;
use App\Modules\ficheros\services\CajaTipoDocumentoService;
use Illuminate\Http\Request;

class CajaTipoDocumentoController extends Controller
{
    public function __construct(private CajaTipoDocumentoService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CajaTipoDocumento::class);

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
        $this->authorize('create', CajaTipoDocumento::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(CajaTipoDocumentoStoreRequest $request)
    {
        $this->authorize('create', CajaTipoDocumento::class);

        $tipoDocumento = $this->service->create($request->validated());

        return response()->json(['data' => $tipoDocumento], 201);
    }

    public function update(CajaTipoDocumentoUpdateRequest $request, CajaTipoDocumento $cajaTipoDocumento)
    {
        $this->authorize('update', $cajaTipoDocumento);

        $updated = $this->service->update($cajaTipoDocumento, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(CajaTipoDocumento $cajaTipoDocumento)
    {
        $this->authorize('deactivate', $cajaTipoDocumento);

        $updated = $this->service->deactivate($cajaTipoDocumento);

        return response()->json(['data' => $updated]);
    }
}
