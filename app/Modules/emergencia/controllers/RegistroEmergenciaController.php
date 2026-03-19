<?php

namespace App\Modules\emergencia\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\RegistroEmergencia;
use App\Modules\admision\models\Paciente;
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

    public function show(int $id)
    {
        $record = RegistroEmergencia::query()->with(['tipoEmergencia'])->findOrFail($id);
        $paciente = Paciente::query()
            ->where('numero_documento', $record->numero_hc)
            ->orWhere('nr', $record->numero_hc)
            ->first();

        $record->setAttribute('paciente', $paciente ? $paciente->toArray() : null);

        return response()->json(['data' => $record]);
    }

    public function store(RegistroEmergenciaStoreRequest $request)
    {
        $data = $request->validated();
        $record = $this->service->create($data);
        return response()->json(['data' => $record], 201);
    }

    public function update(RegistroEmergenciaStoreRequest $request, int $id)
    {
        $data = $request->validated();
        $record = $this->service->update($data, $id);
        return response()->json(['data' => $record]);
    }
}
