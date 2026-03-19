<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\ServicioDefaultEmergencia;
use App\Modules\admision\models\Tarifa;
use Illuminate\Http\Request;

class ServicioDefaultEmergenciaController extends Controller
{
    public function show($tarifaId)
    {
        $servicios = ServicioDefaultEmergencia::where('tarifa_id', $tarifaId)
            ->pluck('codigo_servicio');

        return response()->json(['data' => $servicios]);
    }

    public function update(Request $request, $tarifaId)
    {
        $validated = $request->validate([
            'servicios' => 'array',
            'servicios.*' => 'string'
        ]);

        Tarifa::findOrFail($tarifaId);

        $servicios = array_values(array_unique($validated['servicios'] ?? []));

        ServicioDefaultEmergencia::where('tarifa_id', $tarifaId)->delete();

        $inserts = array_map(function ($codigo) use ($tarifaId) {
            return [
                'tarifa_id' => $tarifaId,
                'codigo_servicio' => $codigo,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $servicios);

        if (!empty($inserts)) {
            ServicioDefaultEmergencia::insert($inserts);
        }

        return response()->json([
            'data' => $servicios
        ]);
    }
}
