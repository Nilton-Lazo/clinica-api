<?php

namespace App\Modules\ficheros\requests;

use Illuminate\Foundation\Http\FormRequest;

class TarifaRecargoNocheUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'porcentaje' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'hora_desde' => ['sometimes', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'hora_hasta' => ['sometimes', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'estado' => ['sometimes', 'string', 'in:ACTIVO,INACTIVO,SUSPENDIDO'],
        ];
    }

    public function messages(): array
    {
        return [
            'porcentaje.numeric' => 'El porcentaje del recargo nocturno debe ser numérico.',
            'porcentaje.min' => 'El porcentaje del recargo nocturno no puede ser menor a 0.',
            'porcentaje.max' => 'El porcentaje del recargo nocturno no puede superar 100.',
            'hora_desde.regex' => 'Ingresa una hora de inicio válida para el recargo nocturno.',
            'hora_hasta.regex' => 'Ingresa una hora de fin válida para el recargo nocturno.',
            'estado.in' => 'Selecciona un estado válido para el recargo nocturno.',
        ];
    }
}

