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
            'observaciones_cierre' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
