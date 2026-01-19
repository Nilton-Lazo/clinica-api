<?php

namespace App\Modules\admision\controllers\pacientes;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\models\PacientePlan;
use App\Modules\admision\requests\pacientes\PacientePlanStoreRequest;
use App\Modules\admision\requests\pacientes\PacienteStoreRequest;
use App\Modules\admision\requests\pacientes\PacienteUpdateRequest;
use App\Modules\admision\services\pacientes\PacienteService;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    public function __construct(private PacienteService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Paciente::class);

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

    public function show(Paciente $paciente)
    {
        $this->authorize('view', $paciente);

        $full = $this->service->loadFull($paciente);

        return response()->json(['data' => $full]);
    }

    public function store(PacienteStoreRequest $request)
    {
        $this->authorize('create', Paciente::class);

        $paciente = $this->service->create($request->validated());

        return response()->json(['data' => $paciente], 201);
    }

    public function update(PacienteUpdateRequest $request, Paciente $paciente)
    {
        $this->authorize('update', $paciente);

        $updated = $this->service->update($paciente, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Paciente $paciente)
    {
        $this->authorize('deactivate', $paciente);

        $updated = $this->service->deactivate($paciente);

        return response()->json(['data' => $updated]);
    }

    public function addPlan(PacientePlanStoreRequest $request, Paciente $paciente)
    {
        $this->authorize('update', $paciente);

        $plan = $this->service->addPlan($paciente, (int)$request->validated()['tipo_cliente_id']);

        return response()->json(['data' => $plan], 201);
    }

    public function deactivatePlan(PacientePlan $plan)
    {
        $this->authorize('update', Paciente::class);

        $updated = $this->service->deactivatePlan($plan);

        return response()->json(['data' => $updated]);
    }
}
