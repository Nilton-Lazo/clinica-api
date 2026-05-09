<?php

namespace App\Modules\caja\requests;

use App\Modules\caja\models\CajaApertura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CajaAperturaCloseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'string', Rule::in([CajaApertura::TIPO_NORMAL, CajaApertura::TIPO_CHICA])],
            'monto_cierre' => ['nullable', 'numeric', 'min:0'],
            'ajuste_cierre' => ['nullable', 'numeric'],
            'observaciones_cierre' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Selecciona el tipo de caja que deseas cerrar.',
            'tipo.string' => 'El tipo de caja debe ser texto.',
            'tipo.in' => 'El tipo de caja seleccionado no es válido. Debe ser caja normal o caja chica.',
            'monto_cierre.numeric' => 'El monto de cierre debe ser un número válido.',
            'monto_cierre.min' => 'El monto de cierre no puede ser menor a 0.',
            'ajuste_cierre.numeric' => 'El ajuste calculado de cierre debe ser un número válido.',
            'observaciones_cierre.string' => 'Las observaciones de cierre deben ser texto.',
            'observaciones_cierre.max' => 'Las observaciones de cierre no deben superar 5000 caracteres.',
        ];
    }
}
