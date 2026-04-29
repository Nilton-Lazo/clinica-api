<?php

namespace App\Modules\ficheros\requests;

use Illuminate\Foundation\Http\FormRequest;

class TarifaRecargoNocheStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tarifa_categoria_id' => ['required', 'integer', 'min:1'],
            'porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'hora_desde' => ['sometimes', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'hora_hasta' => ['sometimes', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'estado' => ['sometimes', 'string', 'in:ACTIVO,INACTIVO,SUSPENDIDO'],
        ];
    }

    public function messages(): array
    {
        return [
            'tarifa_categoria_id.required' => 'Selecciona la categoría del tarifario para aplicar el recargo nocturno.',
            'tarifa_categoria_id.integer' => 'Selecciona una categoría válida para el recargo nocturno.',
            'tarifa_categoria_id.min' => 'Selecciona una categoría válida para el recargo nocturno.',
            'porcentaje.required' => 'Ingresa el porcentaje del recargo nocturno.',
            'porcentaje.numeric' => 'El porcentaje del recargo nocturno debe ser numérico.',
            'porcentaje.min' => 'El porcentaje del recargo nocturno no puede ser menor a 0.',
            'porcentaje.max' => 'El porcentaje del recargo nocturno no puede superar 100.',
            'hora_desde.regex' => 'Ingresa una hora de inicio válida para el recargo nocturno.',
            'hora_hasta.regex' => 'Ingresa una hora de fin válida para el recargo nocturno.',
            'estado.in' => 'Selecciona un estado válido para el recargo nocturno.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('hora_desde') || trim((string)$this->input('hora_desde')) === '') {
            $this->merge(['hora_desde' => '19:00']);
        }
        $horaDesde = trim((string)$this->input('hora_desde', '19:00'));
        if ($horaDesde !== '' && (!$this->has('hora_hasta') || trim((string)$this->input('hora_hasta')) === '')) {
            $this->merge(['hora_hasta' => $this->horaDesdeMas12($horaDesde)]);
        }
    }

    private function horaDesdeMas12(string $hora): string
    {
        $parts = explode(':', $hora);
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
        $h = ($h + 12) % 24;
        return sprintf('%02d:%02d', $h, $m);
    }
}

