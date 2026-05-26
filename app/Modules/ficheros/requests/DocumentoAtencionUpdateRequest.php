<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentoAtencionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $documentoAtencion = $this->route('documentoAtencion');

        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('documento_atencion', 'codigo')->ignore($documentoAtencion->id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
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
            'estado.required' => 'Selecciona el estado del documento de atención.',
            'estado.in' => 'Selecciona un estado válido para el documento de atención.',
        ];
    }
}
