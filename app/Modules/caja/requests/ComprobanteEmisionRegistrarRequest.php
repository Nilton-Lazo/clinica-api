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
            'emision_origen' => ['required', 'string'],
            'nro_cuenta' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'numeracion_id' => ['required', 'integer', 'min:1', 'exists:caja_numeraciones_comprobante,id'],
            'forma_pago_id' => ['required', 'integer', 'min:1', 'exists:caja_formas_pago,id'],
            'medio_pago_id' => ['required', 'integer', 'min:1', 'exists:caja_medios_pago,id'],
            'banco_tarjeta_id' => ['nullable', 'integer', 'min:1', 'exists:caja_bancos_tarjetas,id'],
            'servicio_linea_ids' => ['present', 'array'],
            'servicio_linea_ids.*' => ['integer', 'min:1'],
            'numero_operacion' => ['nullable', 'string', 'max:120'],
            'fecha_vencimiento' => ['nullable', 'date_format:Y-m-d'],
            'snapshot' => ['required', 'array'],
            'adelanto' => ['sometimes', 'array'],
            'adelanto.enabled' => ['sometimes', 'boolean'],
            'adelanto.servicio_codigo' => ['sometimes', 'string', 'max:60'],
            'adelanto.monto_con_igv' => ['sometimes', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,4})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'emision_origen.required' => 'Selecciona el origen del comprobante antes de registrar la emisión.',
            'emision_origen.string' => 'El origen del comprobante no es válido.',
            'nro_cuenta.required' => 'Ingresa el número de cuenta antes de registrar la emisión.',
            'nro_cuenta.string' => 'El número de cuenta debe ser texto.',
            'nro_cuenta.regex' => 'El número de cuenta debe tener 10 dígitos.',
            'numeracion_id.required' => 'Selecciona la serie del comprobante antes de registrar la emisión.',
            'numeracion_id.integer' => 'Selecciona una serie de comprobante válida.',
            'numeracion_id.min' => 'Selecciona una serie de comprobante válida.',
            'numeracion_id.exists' => 'La serie seleccionada no existe en la numeración de comprobantes.',
            'forma_pago_id.required' => 'Selecciona la forma de pago antes de registrar la emisión.',
            'forma_pago_id.integer' => 'Selecciona una forma de pago válida.',
            'forma_pago_id.min' => 'Selecciona una forma de pago válida.',
            'forma_pago_id.exists' => 'La forma de pago seleccionada no existe o ya no está disponible.',
            'medio_pago_id.required' => 'Selecciona el medio de pago antes de registrar la emisión.',
            'medio_pago_id.integer' => 'Selecciona un medio de pago válido.',
            'medio_pago_id.min' => 'Selecciona un medio de pago válido.',
            'medio_pago_id.exists' => 'El medio de pago seleccionado no existe o ya no está disponible.',
            'banco_tarjeta_id.integer' => 'Selecciona un banco o tarjeta válido.',
            'banco_tarjeta_id.min' => 'Selecciona un banco o tarjeta válido.',
            'banco_tarjeta_id.exists' => 'El banco o tarjeta seleccionado no existe o ya no está disponible.',
            'servicio_linea_ids.present' => 'Carga los servicios pendientes de la cuenta antes de registrar la emisión.',
            'servicio_linea_ids.array' => 'La lista de servicios a facturar debe tener un formato válido.',
            'servicio_linea_ids.*.integer' => 'Cada servicio a facturar debe tener un identificador válido.',
            'servicio_linea_ids.*.min' => 'Cada servicio a facturar debe tener un identificador válido.',
            'numero_operacion.string' => 'El número de operación debe ser texto.',
            'numero_operacion.max' => 'El número de operación no debe superar 120 caracteres.',
            'fecha_vencimiento.date_format' => 'La fecha de vencimiento debe tener el formato YYYY-MM-DD.',
            'snapshot.required' => 'No se recibió el contexto de la emisión. Recarga la cuenta e inténtalo nuevamente.',
            'snapshot.array' => 'El contexto de la emisión debe tener un formato válido.',
            'adelanto.array' => 'Los datos del adelanto deben tener un formato válido.',
            'adelanto.enabled.boolean' => 'El estado del adelanto debe ser verdadero o falso.',
            'adelanto.servicio_codigo.string' => 'El código del servicio de adelanto debe ser texto.',
            'adelanto.servicio_codigo.max' => 'El código del servicio de adelanto no debe superar 60 caracteres.',
            'adelanto.monto_con_igv.numeric' => 'El monto del adelanto debe ser numérico.',
            'adelanto.monto_con_igv.min' => 'El monto del adelanto no puede ser negativo.',
            'adelanto.monto_con_igv.regex' => 'El monto del adelanto admite hasta 4 decimales.',
        ];
    }
}
