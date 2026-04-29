<?php

namespace App\Modules\caja\requests;

use Illuminate\Foundation\Http\FormRequest;

class ComprobanteEmisionRegistrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nro_cuenta' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'numeracion_id' => ['required', 'integer', 'min:1', 'exists:caja_numeraciones_comprobante,id'],
            'servicio_linea_ids' => ['present', 'array'],
            'servicio_linea_ids.*' => ['integer', 'min:1'],
            'numero_operacion' => ['nullable', 'string', 'max:120'],
            'fecha_vencimiento' => ['nullable', 'date_format:Y-m-d'],
            'snapshot' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'nro_cuenta.required' => 'Ingresa el número de cuenta antes de registrar la emisión.',
            'nro_cuenta.string' => 'El número de cuenta debe ser texto.',
            'nro_cuenta.regex' => 'El número de cuenta debe tener 10 dígitos.',
            'numeracion_id.required' => 'Selecciona la serie del comprobante antes de registrar la emisión.',
            'numeracion_id.integer' => 'Selecciona una serie de comprobante válida.',
            'numeracion_id.min' => 'Selecciona una serie de comprobante válida.',
            'numeracion_id.exists' => 'La serie seleccionada no existe en la numeración de comprobantes.',
            'servicio_linea_ids.present' => 'Carga los servicios pendientes de la cuenta antes de registrar la emisión.',
            'servicio_linea_ids.array' => 'La lista de servicios a facturar debe tener un formato válido.',
            'servicio_linea_ids.*.integer' => 'Cada servicio a facturar debe tener un identificador válido.',
            'servicio_linea_ids.*.min' => 'Cada servicio a facturar debe tener un identificador válido.',
            'numero_operacion.string' => 'El número de operación debe ser texto.',
            'numero_operacion.max' => 'El número de operación no debe superar 120 caracteres.',
            'fecha_vencimiento.date_format' => 'La fecha de vencimiento debe tener el formato YYYY-MM-DD.',
            'snapshot.required' => 'No se recibió el contexto de la emisión. Recarga la cuenta e inténtalo nuevamente.',
            'snapshot.array' => 'El contexto de la emisión debe tener un formato válido.',
        ];
    }
}
