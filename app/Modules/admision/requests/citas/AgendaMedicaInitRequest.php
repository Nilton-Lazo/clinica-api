<?php

namespace App\Modules\admision\requests\citas;

use Illuminate\Foundation\Http\FormRequest;

class AgendaMedicaInitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'string', 'date_format:Y-m-d'],
        ];
    }
}
