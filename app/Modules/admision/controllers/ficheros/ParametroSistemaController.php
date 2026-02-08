<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\ParametroSistema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParametroSistemaController extends Controller
{
    public function getIgv(): JsonResponse
    {
        return response()->json([
            'igv_porcentaje' => ParametroSistema::getIgvPorcentaje(),
        ]);
    }

    public function updateIgv(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'igv_porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        ParametroSistema::setValor(
            'igv_porcentaje',
            (string)round((float)$valid['igv_porcentaje'], 2),
            'Porcentaje de IGV aplicable'
        );

        return response()->json([
            'igv_porcentaje' => ParametroSistema::getIgvPorcentaje(),
        ]);
    }
}
