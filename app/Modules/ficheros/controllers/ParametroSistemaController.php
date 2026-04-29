<?php

namespace App\Modules\ficheros\controllers;

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
        ], [
            'igv_porcentaje.required' => 'Ingresa el porcentaje de IGV.',
            'igv_porcentaje.numeric' => 'El porcentaje de IGV debe ser numérico.',
            'igv_porcentaje.min' => 'El porcentaje de IGV no puede ser menor a 0.',
            'igv_porcentaje.max' => 'El porcentaje de IGV no puede superar 100.',
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

