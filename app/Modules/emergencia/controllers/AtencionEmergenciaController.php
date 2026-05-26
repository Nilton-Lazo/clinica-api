<?php

namespace App\Modules\emergencia\controllers;

use App\Http\Controllers\Controller;
use App\Modules\emergencia\requests\AtencionEmergenciaStoreRequest;
use App\Modules\emergencia\services\AtencionEmergenciaService;
use Illuminate\Http\JsonResponse;

class AtencionEmergenciaController extends Controller
{
    public function __construct(private AtencionEmergenciaService $service) {}

    public function show(int $id): JsonResponse
    {
        $payload = $this->service->datosParaAtencion($id);
        return response()->json($payload);
    }

    public function store(AtencionEmergenciaStoreRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $payload = $this->service->guardarAtencion($id, $data);

        return response()->json($payload);
    }
}
