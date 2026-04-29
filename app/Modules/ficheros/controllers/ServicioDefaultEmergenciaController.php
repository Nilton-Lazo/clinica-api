<?php

namespace App\Modules\ficheros\controllers;

use App\Core\support\RecordStatus;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\ServicioDefaultEmergencia;
use App\Modules\admision\models\Tarifa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $request->merge(['tarifa_id' => (int) $tarifaId]);

        $validated = $request->validate([
            'servicios' => ['array'],
            'servicios.*' => ['string', 'max:50'],
        ], [
            'servicios.array' => 'La lista de servicios por defecto no tiene un formato válido.',
            'servicios.*.string' => 'Cada servicio por defecto debe tener un código válido.',
            'servicios.*.max' => 'El código de cada servicio por defecto no debe superar 50 caracteres.',
        ]);

        $request->validate([
            'tarifa_id' => [
                'required',
                'integer',
                Rule::exists('tarifas', 'id')->where('estado', RecordStatus::ACTIVO->value),
            ],
        ], [
            'tarifa_id.required' => 'Selecciona el tarifario para configurar servicios por defecto.',
            'tarifa_id.integer' => 'Selecciona un tarifario válido.',
            'tarifa_id.exists' => 'El tarifario seleccionado no existe o está inactivo.',
        ]);

        Tarifa::where('estado', RecordStatus::ACTIVO->value)->findOrFail($tarifaId);

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
