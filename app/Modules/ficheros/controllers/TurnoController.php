<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\Turno;
use App\Modules\ficheros\requests\TurnoStoreRequest;
use App\Modules\ficheros\requests\TurnoUpdateRequest;
use App\Modules\ficheros\services\TurnoService;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    public function __construct(private TurnoService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Turno::class);

        $params = GridParams::fromRequest($request, ['codigo', 'descripcion', 'estado'], 'codigo');

        return GridResponse::fromPaginator($this->service->paginate($params));
    }

    public function nextCodigo()
    {
        $this->authorize('create', Turno::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(TurnoStoreRequest $request)
    {
        $this->authorize('create', Turno::class);

        $turno = $this->service->create($request->validated());

        return response()->json(['data' => $turno], 201);
    }

    public function update(TurnoUpdateRequest $request, Turno $turno)
    {
        $this->authorize('update', $turno);

        $updated = $this->service->update($turno, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Turno $turno)
    {
        $this->authorize('deactivate', $turno);

        $updated = $this->service->deactivate($turno);

        return response()->json(['data' => $updated]);
    }
}

