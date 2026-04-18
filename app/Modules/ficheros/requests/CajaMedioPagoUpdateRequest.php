<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CajaMedioPagoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('cajaMedioPago')?->id;
        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('caja_medios_pago', 'codigo')->ignore($id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
            'forma_pago_ids' => ['required', 'array', 'min:1'],
            'forma_pago_ids.*' => ['integer', Rule::exists('caja_formas_pago', 'id')->where('estado', RecordStatus::ACTIVO->value)],
        ];
    }
}
