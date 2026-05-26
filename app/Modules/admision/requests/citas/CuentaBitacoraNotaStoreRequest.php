<?php

namespace App\Modules\admision\requests\citas;

use Illuminate\Foundation\Http\FormRequest;

class CuentaBitacoraNotaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contenido' => ['required', 'string', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'contenido.required' => 'Escribe el contenido de la nota de bitácora.',
            'contenido.string' => 'La nota de bitácora debe ser texto.',
            'contenido.max' => 'La nota de bitácora no debe superar 4000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $c = $this->input('contenido');
        if (is_string($c)) {
            $this->merge(['contenido' => trim($c)]);
        }
    }
}
