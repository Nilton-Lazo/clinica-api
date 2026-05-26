<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultorioUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('consultorio')?->id ?? $this->route('consultorio');

        return [
            'abreviatura' => ['required', 'string', 'max:10', 'regex:/^[0-9A-Za-z\.\-_]+$/', Rule::unique('consultorios', 'abreviatura')->ignore($id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'es_tercero' => ['required', 'boolean'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'abreviatura.required' => 'Ingresa la abreviatura del consultorio.',
            'abreviatura.max' => 'La abreviatura del consultorio no debe superar 10 caracteres.',
            'abreviatura.regex' => 'La abreviatura del consultorio solo puede contener letras, números, punto, guion o guion bajo.',
            'abreviatura.unique' => 'Ya existe otro consultorio con esa abreviatura.',
            'descripcion.required' => 'Ingresa la descripción del consultorio.',
            'descripcion.string' => 'La descripción del consultorio debe ser texto.',
            'descripcion.max' => 'La descripción del consultorio no debe superar 255 caracteres.',
            'es_tercero.required' => 'Indica si el consultorio es tercero.',
            'es_tercero.boolean' => 'Indica si el consultorio es tercero con un valor válido.',
            'estado.required' => 'Selecciona el estado del consultorio.',
            'estado.in' => 'El estado del consultorio debe ser ACTIVO o INACTIVO.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('es_tercero') && $this->has('es_terceros')) {
            $this->merge(['es_tercero' => $this->boolean('es_terceros')]);
        }

        if ($this->has('es_tercero')) {
            $this->merge(['es_tercero' => $this->boolean('es_tercero')]);
        }
    }
}

