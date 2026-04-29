<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifaCategoriaUpdateRequest extends FormRequest
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
            'descripcion.required' => 'Ingresa la descripción de la categoría.',
            'descripcion.string' => 'La descripción de la categoría debe ser texto.',
            'descripcion.max' => 'La descripción de la categoría no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado de la categoría.',
            'estado.in' => 'El estado de la categoría debe ser ACTIVO o INACTIVO.',
        ];
    }
}

