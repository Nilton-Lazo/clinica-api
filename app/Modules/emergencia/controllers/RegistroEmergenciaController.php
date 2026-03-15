<?php

namespace App\Modules\emergencia\controllers;

use App\Http\Controllers\Controller;
use App\Modules\emergencia\requests\RegistroEmergenciaStoreRequest;
use App\Modules\emergencia\services\RegistroEmergenciaService;
use Illuminate\Http\Request;

class RegistroEmergenciaController extends Controller
{
    public function __construct(private RegistroEmergenciaService $service) {}

    public function index(Request $request)
    {
        $p = $this->service->paginate($request->only(['q', 'fecha_desde', 'fecha_hasta', 'per_page', 'page']));

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

    public function nextOrden(Request $request)
    {
        $fecha = $request->query('fecha');
        $orden = $this->service->nextOrdenForDate($fecha);
        return response()->json(['orden' => $orden]);
    }

    public function store(RegistroEmergenciaStoreRequest $request)
    {
        $data = $request->validated();
        $record = $this->service->create($data);
        return response()->json(['data' => $record], 201);
    }
}
