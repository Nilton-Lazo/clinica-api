<?php

namespace App\Modules\ficheros\requests;

use Illuminate\Foundation\Http\FormRequest;

class PaqueteServiciosSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'servicio_ids' => ['present', 'array'],
            'servicio_ids.*' => ['integer', 'distinct', 'min:1'],
        ];
    }
}
