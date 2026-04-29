<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentoAtencionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50', 'unique:documento_atencion,codigo'],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el código del documento de atención.',
            'codigo.unique' => 'Ya existe un documento de atención con ese código.',
            'codigo.max' => 'El código del documento de atención no debe superar 50 caracteres.',
            'descripcion.required' => 'Ingresa la descripción del documento de atención.',
            'descripcion.max' => 'La descripción del documento de atención no debe superar 255 caracteres.',
            'estado.in' => 'Selecciona un estado válido para el documento de atención.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        }
    }
}
