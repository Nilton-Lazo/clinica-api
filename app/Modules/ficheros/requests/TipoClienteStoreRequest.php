<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoClienteStoreRequest extends FormRequest
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

            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.prohibited' => 'El código del tipo de cliente lo genera el sistema; no lo envíes manualmente.',
            'iafa_id.prohibited' => 'La IAFAS del tipo de cliente se obtiene desde la tarifa seleccionada; no la envíes manualmente.',
            'descripcion_tipo_cliente.prohibited' => 'La descripción del tipo de cliente se genera con el contratante y la tarifa; no la envíes manualmente.',
            'tarifa_id.required' => 'Selecciona la tarifa del tipo de cliente.',
            'tarifa_id.integer' => 'Selecciona una tarifa válida para el tipo de cliente.',
            'tarifa_id.exists' => 'La tarifa seleccionada no existe, está inactiva o no tiene IAFAS asociada.',
            'contratante_id.required' => 'Selecciona el contratante del tipo de cliente.',
            'contratante_id.integer' => 'Selecciona un contratante válido para el tipo de cliente.',
            'contratante_id.exists' => 'El contratante seleccionado no existe o está inactivo.',
            'estado.in' => 'El estado del tipo de cliente debe ser ACTIVO o INACTIVO.',
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

        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        } else {
            $this->merge(['estado' => strtoupper(trim((string)$this->input('estado')))]);
        }
    }
}

