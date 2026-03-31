<?php

namespace App\Modules\admision\requests\citas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PresupuestoStoreRequest extends FormRequest
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
            'tarifa_id' => ['nullable', 'integer', 'exists:tarifas,id'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'vigencia_hasta' => ['required', 'date', 'after_or_equal:today'],
            'estado' => ['required', 'string', Rule::in(['VIGENTE', 'UTILIZADO', 'VENCIDO', 'ANULADO'])],
            'monto_a_pagar' => ['required', 'numeric', 'min:0'],
            'payload' => ['required', 'array'],
        ];
    }
}
