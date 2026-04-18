<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CajaBancoTarjetaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('cajaBancoTarjeta')?->id;

        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('caja_bancos_tarjetas', 'codigo')->ignore($id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
            'forma_pago_ids' => ['required', 'array', 'size:1'],
            'forma_pago_ids.*' => ['integer', Rule::exists('caja_formas_pago', 'id')->where('estado', RecordStatus::ACTIVO->value)],
            'medio_pago_ids' => ['required', 'array', 'size:1'],
            'medio_pago_ids.*' => ['integer', Rule::exists('caja_medios_pago', 'id')->where('estado', RecordStatus::ACTIVO->value)],
        ];
    }
}
