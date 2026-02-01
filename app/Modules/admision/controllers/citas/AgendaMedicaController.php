<?php

namespace App\Modules\admision\controllers\citas;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\requests\citas\AgendaCitaStoreRequest;
use App\Modules\admision\requests\citas\AgendaMedicaOptionsRequest;
use App\Modules\admision\requests\citas\AgendaMedicaSlotsRequest;
use App\Modules\admision\services\citas\AgendaMedicaService;
use Illuminate\Http\Request;

class AgendaMedicaController extends Controller
{
    public function __construct(private AgendaMedicaService $service) {}

    public function opciones(AgendaMedicaOptionsRequest $request)
    {
        $this->authorize('viewAny', AgendaCita::class);

        $data = $this->service->opciones($request->validated());

        return response()->json(['data' => $data]);
    }

    public function slots(AgendaMedicaSlotsRequest $request)
    {
        $this->authorize('viewAny', AgendaCita::class);

        $data = $this->service->slots($request->validated());

        return response()->json(['data' => $data]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', AgendaCita::class);

        $filters = $request->only(['fecha', 'especialidad_id', 'medico_id', 'per_page', 'page']);
        $res = $this->service->listarCitas($filters);

        if (!$res['paginator']) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => (int)($filters['per_page'] ?? 50),
                    'total' => 0,
                    'last_page' => 1,
                ],
                'programacion' => null,
            ]);
        }

        $p = $res['paginator'];

        return response()->json([
            'data' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
            'programacion' => $res['programacion'],
        ]);
    }

    public function store(AgendaCitaStoreRequest $request)
    {
        $this->authorize('create', AgendaCita::class);

        $cita = $this->service->crearCita($request->validated());

        return response()->json(['data' => $cita], 201);
    }
}
