<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoIafaUpdateRequest extends FormRequest
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
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.prohibited' => 'El código del tipo de IAFAS lo genera el sistema; no lo modifiques manualmente.',
            'descripcion.required' => 'Ingresa la descripción del tipo de IAFAS.',
            'descripcion.string' => 'La descripción del tipo de IAFAS debe ser texto.',
            'descripcion.max' => 'La descripción del tipo de IAFAS no debe superar 120 caracteres.',
            'estado.required' => 'Selecciona el estado del tipo de IAFAS.',
            'estado.in' => 'El estado del tipo de IAFAS debe ser ACTIVO o INACTIVO.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('descripcion')) {
            $d = trim((string)$this->input('descripcion'));
            $this->merge(['descripcion' => $d]);
        }

        if ($this->has('estado')) {
            $this->merge(['estado' => strtoupper(trim((string)$this->input('estado')))]);
        }
    }
}

