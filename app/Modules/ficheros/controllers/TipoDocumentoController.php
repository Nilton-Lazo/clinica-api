<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\TipoDocumento;
use App\Modules\ficheros\requests\TipoDocumentoStoreRequest;
use App\Modules\ficheros\requests\TipoDocumentoUpdateRequest;
use App\Modules\ficheros\services\TipoDocumentoService;
use Illuminate\Http\Request;

class TipoDocumentoController extends Controller
{
    public function __construct(private TipoDocumentoService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', TipoDocumento::class);

        $params = GridParams::fromRequest($request, ['codigo', 'descripcion', 'estado'], 'codigo');

        return GridResponse::fromPaginator($this->service->paginate($params));
    }

    public function nextCodigo()
    {
        $this->authorize('create', TipoDocumento::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(TipoDocumentoStoreRequest $request)
    {
        $this->authorize('create', TipoDocumento::class);

        $tipoDocumento = $this->service->create($request->validated());

        return response()->json(['data' => $tipoDocumento], 201);
    }

    public function update(TipoDocumentoUpdateRequest $request, TipoDocumento $tipoDocumento)
    {
        $this->authorize('update', $tipoDocumento);

        $updated = $this->service->update($tipoDocumento, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(TipoDocumento $tipoDocumento)
    {
        $this->authorize('deactivate', $tipoDocumento);

        $this->service->deactivate($tipoDocumento);

        return response()->json(['data' => $tipoDocumento->fresh()]);
    }
}
