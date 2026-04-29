<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifaSubcategoriaStoreRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', 'min:1'],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required' => 'Selecciona la categoría de la subcategoría.',
            'categoria_id.integer' => 'Selecciona una categoría válida para la subcategoría.',
            'categoria_id.min' => 'Selecciona una categoría válida para la subcategoría.',
            'descripcion.required' => 'Ingresa la descripción de la subcategoría.',
            'descripcion.string' => 'La descripción de la subcategoría debe ser texto.',
            'descripcion.max' => 'La descripción de la subcategoría no debe superar 255 caracteres.',
            'estado.in' => 'El estado de la subcategoría debe ser ACTIVO o INACTIVO.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        }
    }
}

