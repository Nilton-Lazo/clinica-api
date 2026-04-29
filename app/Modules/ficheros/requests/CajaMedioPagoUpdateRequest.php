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

    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el código del medio de pago.',
            'codigo.unique' => 'Ya existe un medio de pago con ese código.',
            'codigo.max' => 'El código del medio de pago no debe superar 50 caracteres.',
            'descripcion.required' => 'Ingresa la descripción del medio de pago.',
            'descripcion.max' => 'La descripción del medio de pago no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado del medio de pago.',
            'estado.in' => 'Selecciona un estado válido para el medio de pago.',
            'forma_pago_ids.required' => 'Selecciona al menos una forma de pago activa.',
            'forma_pago_ids.array' => 'Selecciona una forma de pago válida.',
            'forma_pago_ids.min' => 'Selecciona al menos una forma de pago activa.',
            'forma_pago_ids.*.integer' => 'Selecciona una forma de pago válida.',
            'forma_pago_ids.*.exists' => 'La forma de pago seleccionada no existe o está inactiva.',
        ];
    }
}
