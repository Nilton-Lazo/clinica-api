<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Paquete;
use App\Modules\admision\models\Tarifa;
use App\Modules\ficheros\requests\PaqueteServiciosSyncRequest;
use App\Modules\ficheros\services\PaqueteServicioService;

class PaqueteServicioController extends Controller
{
    public function __construct(private PaqueteServicioService $service) {}

    public function paquetesPorTarifa(Tarifa $tarifa)
    {
        $this->authorize('viewAny', Tarifa::class);
        $rows = $this->service->listPaquetesPorTarifa($tarifa);

        return response()->json(['data' => $rows]);
    }

    public function arbolPorTarifa(Tarifa $tarifa)
    {
        $this->authorize('viewAny', Tarifa::class);
        $payload = $this->service->arbolServiciosPorTarifa($tarifa);

        return response()->json(['data' => $payload]);
    }

    public function serviciosPorPaquete(Paquete $paquete)
    {
        $this->authorize('viewAny', Tarifa::class);
        $servicios = $this->service->listServiciosPaquete($paquete);

        return response()->json([
            'data' => [
                'paquete' => $paquete->only(['id', 'codigo', 'descripcion', 'tarifa_id']),
                'servicios' => $servicios,
            ],
        ]);
    }

    public function syncServicios(PaqueteServiciosSyncRequest $request, Paquete $paquete)
    {
        $this->authorize('update', $paquete);
        $result = $this->service->syncServicios($paquete, $request->validated()['servicio_ids']);
        $servicios = $this->service->listServiciosPaquete($paquete);

        return response()->json([
            'data' => [
                'summary' => $result,
                'servicios' => $servicios,
            ],
        ]);
    }
}
