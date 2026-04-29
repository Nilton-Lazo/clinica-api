<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AreaJefaturaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('areaJefatura')?->id;
        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('caja_areas_jefaturas', 'codigo')->ignore($id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el código del área o jefatura.',
            'codigo.unique' => 'Ya existe un área o jefatura con ese código.',
            'codigo.max' => 'El código del área o jefatura no debe superar 50 caracteres.',
            'descripcion.required' => 'Ingresa la descripción del área o jefatura.',
            'descripcion.max' => 'La descripción del área o jefatura no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado del área o jefatura.',
            'estado.in' => 'Selecciona un estado válido para el área o jefatura.',
        ];
    }
}
