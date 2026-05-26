<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifaSubcategoriaUpdateRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.required' => 'Ingresa la descripción de la subcategoría.',
            'descripcion.string' => 'La descripción de la subcategoría debe ser texto.',
            'descripcion.max' => 'La descripción de la subcategoría no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado de la subcategoría.',
            'estado.in' => 'El estado de la subcategoría debe ser ACTIVO o INACTIVO.',
        ];
    }
}

