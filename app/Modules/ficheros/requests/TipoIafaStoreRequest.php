<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoIafaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],
            'descripcion' => ['required', 'string', 'max:120'],
            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.prohibited' => 'El código del tipo de IAFAS lo genera el sistema; no lo envíes manualmente.',
            'descripcion.required' => 'Ingresa la descripción del tipo de IAFAS.',
            'descripcion.string' => 'La descripción del tipo de IAFAS debe ser texto.',
            'descripcion.max' => 'La descripción del tipo de IAFAS no debe superar 120 caracteres.',
            'estado.in' => 'El estado del tipo de IAFAS debe ser ACTIVO o INACTIVO.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('descripcion')) {
            $d = trim((string)$this->input('descripcion'));
            $this->merge(['descripcion' => $d]);
        }

        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        } else {
            $this->merge(['estado' => strtoupper(trim((string)$this->input('estado')))]);
        }
    }
}

