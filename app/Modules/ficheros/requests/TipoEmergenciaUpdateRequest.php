<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoEmergenciaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('tipoEmergencia')?->id;
        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('tipo_emergencia', 'codigo')->ignore($id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el código del tipo de emergencia.',
            'codigo.unique' => 'Ya existe un tipo de emergencia con ese código.',
            'codigo.max' => 'El código del tipo de emergencia no debe superar 50 caracteres.',
            'descripcion.required' => 'Ingresa la descripción del tipo de emergencia.',
            'descripcion.max' => 'La descripción del tipo de emergencia no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado del tipo de emergencia.',
            'estado.in' => 'Selecciona un estado válido para el tipo de emergencia.',
        ];
    }
}
