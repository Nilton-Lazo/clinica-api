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
            'fecha_afiliacion' => ['prohibited'],
            'estado' => ['prohibited'],

            'tipo_cliente_id' => [
                'required',
                'integer',
                Rule::exists('tipos_clientes', 'id')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value)),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tipo_cliente_id')) {
            $v = trim((string)$this->input('tipo_cliente_id'));
            $this->merge(['tipo_cliente_id' => $v !== '' ? (int)$v : null]);
        }
    }
}
