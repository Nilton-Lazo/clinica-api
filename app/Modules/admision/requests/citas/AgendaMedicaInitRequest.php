<?php

namespace App\Modules\admision\requests\citas;

use Illuminate\Foundation\Http\FormRequest;

class AgendaMedicaInitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'string', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Selecciona una fecha para cargar la agenda médica.',
            'fecha.string' => 'La fecha de agenda debe ser texto.',
            'fecha.date_format' => 'La fecha de agenda debe tener el formato YYYY-MM-DD.',
        ];
    }
}
