<?php

namespace App\Modules\admision\requests\ficheros;

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
}
