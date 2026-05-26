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

    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el código del banco o tarjeta.',
            'codigo.unique' => 'Ya existe un banco o tarjeta con ese código.',
            'codigo.max' => 'El código del banco o tarjeta no debe superar 50 caracteres.',
            'descripcion.required' => 'Ingresa la descripción del banco o tarjeta.',
            'descripcion.max' => 'La descripción del banco o tarjeta no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado del banco o tarjeta.',
            'estado.in' => 'Selecciona un estado válido para el banco o tarjeta.',
            'forma_pago_ids.required' => 'Selecciona una forma de pago activa.',
            'forma_pago_ids.array' => 'Selecciona una forma de pago válida.',
            'forma_pago_ids.size' => 'Selecciona solo una forma de pago para el banco o tarjeta.',
            'forma_pago_ids.*.integer' => 'Selecciona una forma de pago válida.',
            'forma_pago_ids.*.exists' => 'La forma de pago seleccionada no existe o está inactiva.',
            'medio_pago_ids.required' => 'Selecciona un medio de pago activo.',
            'medio_pago_ids.array' => 'Selecciona un medio de pago válido.',
            'medio_pago_ids.size' => 'Selecciona solo un medio de pago para el banco o tarjeta.',
            'medio_pago_ids.*.integer' => 'Selecciona un medio de pago válido.',
            'medio_pago_ids.*.exists' => 'El medio de pago seleccionado no existe o está inactivo.',
        ];
    }
}
