<?php

namespace App\Modules\login\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('identifier') && is_string($this->identifier)) {
            $this->merge(['identifier' => trim($this->identifier)]);
        }
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password'   => ['required', 'string', 'max:255'],
        ];
    }
}
