<?php

namespace App\Modules\admision\controllers\citas;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\ProgramacionMedica;
use App\Modules\admision\requests\citas\ProgramacionMedicaStoreRequest;
use App\Modules\admision\requests\citas\ProgramacionMedicaUpdateRequest;
use App\Modules\admision\services\citas\ProgramacionMedicaService;
use Illuminate\Http\Request;

class ProgramacionMedicaController extends Controller
{
    public function __construct(private ProgramacionMedicaService $service)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ProgramacionMedica::class);

        $p = $this->service->paginate($request->only(['from', 'to', 'status', 'q', 'per_page', 'page']));

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

    public function nextCodigo(Request $request)
    {
        $this->authorize('viewAny', ProgramacionMedica::class);

        $count = (int) $request->query('count', 1);
        if ($count < 1) $count = 1;
        if ($count > 370) $count = 370;

        $maxId = (int) (ProgramacionMedica::query()->max('id') ?? 0);

        return response()->json([
            'data' => [
                'next_id' => $maxId + 1,
                'last_id' => $maxId + $count,
                'count' => $count,
            ],
        ]);
    }

    public function store(ProgramacionMedicaStoreRequest $request)
    {
        $this->authorize('create', ProgramacionMedica::class);

        $created = $this->service->createBatch($request->validated());

        return response()->json([
            'data' => $created,
            'meta' => ['created' => count($created)],
        ], 201);
    }

    public function update(ProgramacionMedicaUpdateRequest $request, ProgramacionMedica $programacionMedica)
    {
        $this->authorize('update', $programacionMedica);

        $updated = $this->service->update($programacionMedica, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(ProgramacionMedica $programacionMedica)
    {
        $this->authorize('deactivate', $programacionMedica);

        $updated = $this->service->deactivate($programacionMedica);

        return response()->json(['data' => $updated]);
    }

    public function cupos(Request $request)
    {
        $this->authorize('viewAny', ProgramacionMedica::class);

        $medicoId = (int) $request->query('medico_id');
        $turnoId = (int) $request->query('turno_id');

        if ($medicoId <= 0 || $turnoId <= 0) {
            return response()->json([
                'message' => 'medico_id y turno_id son requeridos.',
            ], 422);
        }

        $data = $this->service->calcularCupos($medicoId, $turnoId);

        return response()->json(['data' => $data]);
    }
}
