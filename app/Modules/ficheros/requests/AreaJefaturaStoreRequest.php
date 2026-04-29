<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AreaJefaturaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:50', 'unique:caja_areas_jefaturas,codigo'],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe un área o jefatura con ese código.',
            'codigo.max' => 'El código del área o jefatura no debe superar 50 caracteres.',
            'descripcion.required' => 'Ingresa la descripción del área o jefatura.',
            'descripcion.max' => 'La descripción del área o jefatura no debe superar 255 caracteres.',
            'estado.in' => 'Selecciona un estado válido para el área o jefatura.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        }
    }
}
