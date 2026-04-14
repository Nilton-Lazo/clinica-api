<?php

namespace App\Modules\admision\requests\citas;

use Illuminate\Foundation\Http\FormRequest;

class PreFacturacionHospitalariaRegistroStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'paciente_plan_id' => ['required', 'integer', 'exists:paciente_planes,id'],
            'nro_cuenta' => ['nullable', 'string', 'max:10'],
            'form' => ['required', 'array'],
        ];
    }
}
