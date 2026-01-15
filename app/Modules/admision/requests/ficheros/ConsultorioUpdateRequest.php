<?php

namespace App\Modules\admision\requests\ficheros;

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
