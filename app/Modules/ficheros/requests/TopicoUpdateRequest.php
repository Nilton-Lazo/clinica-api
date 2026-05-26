<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopicoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('topico')?->id;
        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('topicos', 'codigo')->ignore($id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el código del tópico.',
            'codigo.unique' => 'Ya existe un tópico con ese código.',
            'codigo.max' => 'El código del tópico no debe superar 50 caracteres.',
            'descripcion.required' => 'Ingresa la descripción del tópico.',
            'descripcion.max' => 'La descripción del tópico no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado del tópico.',
            'estado.in' => 'Selecciona un estado válido para el tópico.',
        ];
    }
}
