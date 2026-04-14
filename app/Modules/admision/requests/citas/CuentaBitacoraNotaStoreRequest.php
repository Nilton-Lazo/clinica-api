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

    protected function prepareForValidation(): void
    {
        $c = $this->input('contenido');
        if (is_string($c)) {
            $this->merge(['contenido' => trim($c)]);
        }
    }
}
