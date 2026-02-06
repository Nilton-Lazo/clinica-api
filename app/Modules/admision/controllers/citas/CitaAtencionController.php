<?php

namespace App\Modules\admision\controllers\citas;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\requests\citas\CitaAtencionStoreRequest;
use App\Modules\admision\services\citas\CitaAtencionService;
use Illuminate\Http\JsonResponse;

class CitaAtencionController extends Controller
{
    public function __construct(private CitaAtencionService $service) {}

    /**
     * Datos para el formulario de Atención de cita (cita, programación, paciente, planes, atencion existente).
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('viewAny', AgendaCita::class);

        $payload = $this->service->datosParaAtencion($id);

        return response()->json($payload);
    }

    /**
     * Guardar atención o solo actualizar datos (plan, condición, titular).
     */
    public function store(CitaAtencionStoreRequest $request, int $id): JsonResponse
    {
        $cita = AgendaCita::query()->findOrFail($id);
        $this->authorize('update', $cita);

        $data = $request->validated();
        $payload = !empty($data['solo_actualizar_datos'])
            ? $this->service->actualizarSoloDatos($id, $data)
            : $this->service->guardarAtencion($id, $data);

        return response()->json($payload);
    }
}
