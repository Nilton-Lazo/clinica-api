<?php

namespace App\Modules\caja\requests;

use Illuminate\Foundation\Http\FormRequest;

class ComprobanteEmisionRegistrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nro_cuenta' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'servicio_linea_ids' => ['present', 'array'],
            'servicio_linea_ids.*' => ['integer', 'min:1'],
            'numero_operacion' => ['nullable', 'string', 'max:120'],
            'snapshot' => ['required', 'array'],
        ];
    }
}
