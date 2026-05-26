<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EspecialidadUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

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
            'descripcion.required' => 'Ingresa la descripción de la especialidad.',
            'descripcion.string' => 'La descripción de la especialidad debe ser texto.',
            'descripcion.max' => 'La descripción de la especialidad no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado de la especialidad.',
            'estado.in' => 'El estado de la especialidad debe ser ACTIVO o INACTIVO.',
        ];
    }
}

