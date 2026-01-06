<?php

namespace App\Modules\telemetria\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NavigationEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'path' => ['required', 'string', 'max:255'],
            'screen' => ['nullable', 'string', 'max:120'],
            'module' => ['nullable', 'string', 'max:80'],
        ];
    }
}
