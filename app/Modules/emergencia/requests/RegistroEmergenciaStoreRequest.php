<?php

namespace App\Modules\emergencia\requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistroEmergenciaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'orden' => ['nullable', 'string', 'max:50'],
            'hora' => ['nullable', 'string', 'max:20'],
            'numero_hc' => ['required', 'string', 'max:50'],
            'apellidos_nombres' => ['required', 'string', 'max:255'],
            'sexo' => ['nullable', 'string', 'max:20'],
            'tipo_cliente' => ['nullable', 'string', 'max:100'],
            'fecha' => ['required', 'date'],
            'cuenta' => ['nullable', 'string', 'max:100'],
            'medico_emergencia' => ['nullable', 'string', 'max:255'],
            'medico_especialista' => ['nullable', 'string', 'max:255'],
            'topico' => ['nullable', 'string', 'max:100'],
            'numero_cuenta' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:20'],
        ];
    }
}
