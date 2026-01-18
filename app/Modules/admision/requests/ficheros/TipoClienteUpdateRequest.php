<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoClienteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],
            'iafa_id' => ['prohibited'],
            'descripcion_tipo_cliente' => ['prohibited'],

            'tarifa_id' => [
                'required',
                'integer',
                Rule::exists('tarifas', 'id')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value)->whereNotNull('iafa_id')),
            ],

            'contratante_id' => [
                'required',
                'integer',
                Rule::exists('contratantes', 'id')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value)),
            ],

            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tarifa_id')) {
            $v = trim((string)$this->input('tarifa_id'));
            $this->merge(['tarifa_id' => $v !== '' ? (int)$v : null]);
        }

        if ($this->has('contratante_id')) {
            $v = trim((string)$this->input('contratante_id'));
            $this->merge(['contratante_id' => $v !== '' ? (int)$v : null]);
        }

        if ($this->has('estado')) {
            $this->merge(['estado' => strtoupper(trim((string)$this->input('estado')))]);
        }
    }
}
