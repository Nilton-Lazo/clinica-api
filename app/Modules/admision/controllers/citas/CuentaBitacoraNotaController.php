<?php

namespace App\Modules\admision\controllers\citas;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\requests\citas\CuentaBitacoraNotaStoreRequest;
use App\Modules\admision\services\citas\CuentaBitacoraNotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CuentaBitacoraNotaController extends Controller
{
    public function __construct(
        private CuentaBitacoraNotaService $service,
    ) {}

    public function index(Request $request, string $nroCuenta): JsonResponse
    {
        $this->authorize('viewAny', AgendaCita::class);

        $cuenta = Cuenta::query()->where('nro_cuenta', $nroCuenta)->firstOrFail();
        $perPage = (int) $request->query('per_page', 80);
        if ($perPage < 1) {
            $perPage = 80;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $p = $this->service->paginateMergedForCuenta($cuenta, $perPage);
        $items = [];
        foreach ($p->items() as $row) {
            $items[] = CuentaBitacoraNotaService::serializeNota($row);
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }

    public function store(CuentaBitacoraNotaStoreRequest $request, string $nroCuenta): JsonResponse
    {
        $this->authorize('viewAny', AgendaCita::class);

        $cuenta = Cuenta::query()->where('nro_cuenta', $nroCuenta)->firstOrFail();
        $contenido = (string) $request->validated()['contenido'];
        if ($contenido === '') {
            return response()->json(['message' => 'El contenido no puede estar vacío.'], 422);
        }

        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $nota = $this->service->create($cuenta, $user, $contenido);

        return response()->json([
            'data' => CuentaBitacoraNotaService::serializeNota($nota),
        ], 201);
    }

    public function indexByPaciente(Request $request, Paciente $paciente): JsonResponse
    {
        $this->authorize('view', $paciente);

        $perPage = (int) $request->query('per_page', 80);
        if ($perPage < 1) {
            $perPage = 80;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $p = $this->service->paginateForPacientePending($paciente, $perPage);
        $items = [];
        foreach ($p->items() as $row) {
            $items[] = CuentaBitacoraNotaService::serializeNota($row);
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }

    public function storeByPaciente(CuentaBitacoraNotaStoreRequest $request, Paciente $paciente): JsonResponse
    {
        $this->authorize('view', $paciente);

        $contenido = (string) $request->validated()['contenido'];
        if ($contenido === '') {
            return response()->json(['message' => 'El contenido no puede estar vacío.'], 422);
        }

        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $nota = $this->service->createForPaciente($paciente, $user, $contenido);

        return response()->json([
            'data' => CuentaBitacoraNotaService::serializeNota($nota),
        ], 201);
    }
}
