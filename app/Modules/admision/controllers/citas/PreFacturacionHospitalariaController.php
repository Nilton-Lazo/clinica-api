<?php

namespace App\Modules\admision\controllers\citas;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\requests\citas\PreFacturacionHospitalariaRegistroStoreRequest;
use App\Modules\admision\services\citas\PreFacturacionHospitalariaService;
use Illuminate\Http\JsonResponse;

class PreFacturacionHospitalariaController extends Controller
{
    public function __construct(
        private PreFacturacionHospitalariaService $service,
    ) {}

    public function store(PreFacturacionHospitalariaRegistroStoreRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AgendaCita::class);

        $v = $request->validated();
        $nroIn = isset($v['nro_cuenta']) ? trim((string) $v['nro_cuenta']) : '';
        $result = $this->service->guardarRegistro(
            (int) $v['paciente_id'],
            (int) $v['paciente_plan_id'],
            $nroIn !== '' ? $nroIn : null,
            $v['form'],
        );

        return response()->json([
            'data' => $result,
        ]);
    }
}
