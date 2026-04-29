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

    public function messages(): array
    {
        return [
            'servicio_ids.present' => 'Envía la lista de servicios del paquete, aunque esté vacía.',
            'servicio_ids.array' => 'La lista de servicios del paquete debe ser un arreglo.',
            'servicio_ids.*.integer' => 'Cada servicio del paquete debe tener un identificador válido.',
            'servicio_ids.*.distinct' => 'No envíes servicios duplicados para el mismo paquete.',
            'servicio_ids.*.min' => 'Cada servicio del paquete debe tener un identificador válido.',
        ];
    }
}
