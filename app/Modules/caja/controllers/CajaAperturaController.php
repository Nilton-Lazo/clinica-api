<?php

namespace App\Modules\caja\controllers;

use App\Http\Controllers\Controller;
use App\Modules\caja\models\CajaApertura;
use App\Modules\caja\requests\CajaAperturaCloseRequest;
use App\Modules\caja\requests\CajaAperturaStoreRequest;
use App\Modules\caja\services\CajaAperturaService;
use Illuminate\Http\JsonResponse;

class CajaAperturaController extends Controller
{
    public function __construct(private CajaAperturaService $service) {}

    public function nextCodigo(): JsonResponse
    {
        $this->authorize('create', CajaApertura::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function resumen(): JsonResponse
    {
        $this->authorize('viewAny', CajaApertura::class);

        return response()->json([
            'data' => $this->service->resumen(request()->user()),
        ]);
    }

    public function store(CajaAperturaStoreRequest $request): JsonResponse
    {
        $this->authorize('create', CajaApertura::class);

        $row = $this->service->create($request->validated(), $request->user());

        return response()->json(['data' => $row], 201);
    }

    public function close(CajaAperturaCloseRequest $request): JsonResponse
    {
        $this->authorize('update', CajaApertura::class);

        $row = $this->service->close($request->validated(), $request->user());

        return response()->json(['data' => $row]);
    }
}
