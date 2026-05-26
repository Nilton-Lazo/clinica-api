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

    public function messages(): array
    {
        return [
            'paciente_id.required' => 'Selecciona un paciente para generar el presupuesto.',
            'paciente_id.integer' => 'El paciente seleccionado no es válido.',
            'paciente_id.exists' => 'El paciente seleccionado no existe.',
            'paciente_plan_id.required' => 'Selecciona un plan del paciente para generar el presupuesto.',
            'paciente_plan_id.integer' => 'El plan del paciente seleccionado no es válido.',
            'paciente_plan_id.exists' => 'El plan del paciente seleccionado no existe.',
            'tarifa_id.integer' => 'La tarifa seleccionada no es válida.',
            'tarifa_id.exists' => 'La tarifa seleccionada no existe.',
            'cliente_id.integer' => 'El cliente seleccionado no es válido.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'vigencia_hasta.required' => 'Indica la fecha de vigencia del presupuesto.',
            'vigencia_hasta.date' => 'La fecha de vigencia del presupuesto no tiene un formato válido.',
            'vigencia_hasta.after_or_equal' => 'La vigencia del presupuesto no puede ser anterior a la fecha actual.',
            'estado.required' => 'Selecciona el estado del presupuesto.',
            'estado.string' => 'El estado del presupuesto debe ser texto.',
            'estado.in' => 'El estado del presupuesto no es válido.',
            'monto_a_pagar.required' => 'El monto a pagar del presupuesto es obligatorio.',
            'monto_a_pagar.numeric' => 'El monto a pagar del presupuesto debe ser numérico.',
            'monto_a_pagar.min' => 'El monto a pagar del presupuesto no puede ser negativo.',
            'payload.required' => 'No se recibió el detalle del presupuesto.',
            'payload.array' => 'El detalle del presupuesto tiene un formato inválido.',
        ];
    }
}
