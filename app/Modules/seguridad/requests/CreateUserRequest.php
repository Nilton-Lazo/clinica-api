<?php

namespace App\Modules\seguridad\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nombres'           => ['required', 'string', 'max:100'],
            'apellido_paterno'  => ['required', 'string', 'max:100'],
            'apellido_materno'  => ['nullable', 'string', 'max:100'],
            'email'             => ['required', 'email', 'unique:users,email'],
            'password'          => ['required', 'string', 'min:8'],
            'nivel'             => ['required', 'string'],
            'estado'            => ['required', 'in:activo,inactivo'],
        ];
    }
}
