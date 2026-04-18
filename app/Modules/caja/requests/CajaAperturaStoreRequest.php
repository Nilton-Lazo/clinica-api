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
}
