<?php

namespace App\Modules\admision\controllers\citas;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\models\Presupuesto;
use App\Modules\admision\requests\citas\PresupuestoIndexRequest;
use App\Modules\admision\requests\citas\PresupuestoStoreRequest;
use App\Modules\admision\services\citas\PresupuestoService;
use Illuminate\Http\JsonResponse;

class PresupuestoController extends Controller
{
    public function __construct(private PresupuestoService $service) {}

    public function show(int $id): JsonResponse
    {
        $this->authorize('viewAny', Paciente::class);

        $p = Presupuesto::query()->find($id);
        if (! $p) {
            return response()->json(['message' => 'Presupuesto no encontrado.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'paciente_id' => $p->paciente_id,
                'paciente_plan_id' => $p->paciente_plan_id,
                'tarifa_id' => $p->tarifa_id,
                'cliente_id' => $p->cliente_id,
                'vigencia_hasta' => $p->vigencia_hasta?->toDateString(),
                'estado' => $p->estado,
                'monto_a_pagar' => (string) $p->monto_a_pagar,
                'payload' => $p->payload,
                'created_at' => $p->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function index(PresupuestoIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Paciente::class);

        $request->validated();
        $params = GridParams::fromRequest($request, [
            'codigo',
            'hc',
            'nombre_completo',
            'vigencia_hasta',
            'estado',
            'created_at',
        ], 'created_at');

        return GridResponse::fromPaginator($this->service->paginate($params));
    }

    public function nextCodigo(): JsonResponse
    {
        $this->authorize('viewAny', Paciente::class);

        return response()->json([
            'data' => ['codigo' => $this->service->previewNextCodigo()],
        ]);
    }

    public function store(PresupuestoStoreRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Paciente::class);

        $data = $request->validated();
        $userId = $request->user()?->id;

        $result = $this->service->store($data, $userId ? (int) $userId : null);
        $p = $result['presupuesto'];

        return response()->json([
            'data' => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'paciente_id' => $p->paciente_id,
                'paciente_plan_id' => $p->paciente_plan_id,
                'vigencia_hasta' => $p->vigencia_hasta?->toDateString(),
                'estado' => $p->estado,
                'monto_a_pagar' => (string) $p->monto_a_pagar,
                'created_at' => $p->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
