<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CajaFormaPagoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('cajaFormaPago')?->id;
        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('caja_formas_pago', 'codigo')->ignore($id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el código de la forma de pago.',
            'codigo.unique' => 'Ya existe una forma de pago con ese código.',
            'codigo.max' => 'El código de la forma de pago no debe superar 50 caracteres.',
            'descripcion.required' => 'Ingresa la descripción de la forma de pago.',
            'descripcion.max' => 'La descripción de la forma de pago no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado de la forma de pago.',
            'estado.in' => 'Selecciona un estado válido para la forma de pago.',
        ];
    }
}
