<?php

namespace App\Modules\admision\requests\pacientes;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PacientePlanStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo_cliente_id' => [
                'required',
                'integer',
                Rule::exists('tipos_clientes', 'id')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value)),
            ],
            'fecha_afiliacion' => ['nullable', 'date'],
            'estado' => ['nullable', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_cliente_id.required' => 'Selecciona el tipo de cliente para afiliar el plan.',
            'tipo_cliente_id.integer' => 'El tipo de cliente seleccionado no es válido.',
            'tipo_cliente_id.exists' => 'El tipo de cliente seleccionado no existe o no está activo.',
            'fecha_afiliacion.date' => 'La fecha de afiliación del plan no tiene un formato válido.',
            'estado.string' => 'El estado del plan afiliado debe ser texto.',
            'estado.in' => 'El estado del plan afiliado no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tipo_cliente_id')) {
            $v = trim((string)$this->input('tipo_cliente_id'));
            $this->merge(['tipo_cliente_id' => $v !== '' ? (int)$v : null]);
        }

        if ($this->has('estado') && $this->input('estado') !== null && $this->input('estado') !== '') {
            $this->merge(['estado' => strtoupper(trim((string)$this->input('estado')))]);
        }
    }
}
