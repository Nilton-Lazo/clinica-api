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

    public function messages(): array
    {
        return [
            'paciente_id.required' => 'Selecciona un paciente para registrar la pre-facturación hospitalaria.',
            'paciente_id.integer' => 'El paciente seleccionado no es válido.',
            'paciente_id.exists' => 'El paciente seleccionado no existe.',
            'paciente_plan_id.required' => 'Selecciona un plan del paciente para registrar la pre-facturación hospitalaria.',
            'paciente_plan_id.integer' => 'El plan del paciente seleccionado no es válido.',
            'paciente_plan_id.exists' => 'El plan del paciente seleccionado no existe.',
            'nro_cuenta.string' => 'El número de cuenta debe ser texto.',
            'nro_cuenta.max' => 'El número de cuenta no debe superar 10 caracteres.',
            'form.required' => 'No se recibieron los datos de pre-facturación hospitalaria.',
            'form.array' => 'Los datos de pre-facturación hospitalaria tienen un formato inválido.',
        ];
    }
}
