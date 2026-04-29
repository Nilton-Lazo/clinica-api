<?php

namespace App\Modules\caja\requests;

use App\Modules\caja\models\CajaApertura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CajaAperturaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'string', Rule::in([CajaApertura::TIPO_NORMAL, CajaApertura::TIPO_CHICA])],
            'user_entrega_id' => ['required', 'integer', Rule::exists('users', 'id')->where('estado', 'activo')],
            'area_jefatura_id' => ['required', 'integer', Rule::exists('caja_areas_jefaturas', 'id')->where('estado', 'ACTIVO')],
            'monto_inicio' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Selecciona el tipo de caja que deseas aperturar.',
            'tipo.string' => 'El tipo de caja debe ser texto.',
            'tipo.in' => 'El tipo de caja seleccionado no es válido. Debe ser caja normal o caja chica.',
            'user_entrega_id.required' => 'Selecciona el personal que entrega el monto inicial de caja.',
            'user_entrega_id.integer' => 'Selecciona un personal que entrega válido.',
            'user_entrega_id.exists' => 'El personal que entrega no existe o no está activo.',
            'area_jefatura_id.required' => 'Selecciona el área o jefatura responsable de la apertura.',
            'area_jefatura_id.integer' => 'Selecciona un área o jefatura válida.',
            'area_jefatura_id.exists' => 'El área o jefatura seleccionada no existe o no está activa.',
            'monto_inicio.required' => 'Ingresa el monto inicial de caja.',
            'monto_inicio.numeric' => 'El monto inicial de caja debe ser numérico.',
            'monto_inicio.min' => 'El monto inicial de caja no puede ser negativo.',
            'observaciones.string' => 'Las observaciones de apertura deben ser texto.',
            'observaciones.max' => 'Las observaciones de apertura no deben superar 5000 caracteres.',
        ];
    }
}
