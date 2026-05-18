<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\Cirugia;
use App\Modules\ficheros\requests\CirugiaStoreRequest;
use App\Modules\ficheros\requests\CirugiaUpdateRequest;
use App\Modules\ficheros\services\CirugiaService;
use Illuminate\Http\Request;

class CirugiaController extends Controller
{
    public function __construct(private CirugiaService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Cirugia::class);

        $params = GridParams::fromRequest($request, ['codigo', 'descripcion', 'estado'], 'codigo');

        return GridResponse::fromPaginator($this->service->paginate($params));
    }

    public function nextCodigo()
    {
        $this->authorize('create', Cirugia::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(CirugiaStoreRequest $request)
    {
        $this->authorize('create', Cirugia::class);

        $cirugia = $this->service->create($request->validated());

        return response()->json(['data' => $cirugia], 201);
    }

    public function update(CirugiaUpdateRequest $request, Cirugia $cirugia)
    {
        $this->authorize('update', $cirugia);

        $updated = $this->service->update($cirugia, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Cirugia $cirugia)
    {
        $this->authorize('deactivate', $cirugia);

        $updated = $this->service->deactivate($cirugia);

        return response()->json(['data' => $updated]);
    }
}
