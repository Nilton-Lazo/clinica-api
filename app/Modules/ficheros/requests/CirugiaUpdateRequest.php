<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CirugiaUpdateRequest extends FormRequest
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
            'descripcion.required' => 'Ingresa la descripción de la cirugía.',
            'descripcion.max' => 'La descripción de la cirugía no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado de la cirugía.',
            'estado.in' => 'Selecciona un estado válido para la cirugía.',
        ];
    }
}
