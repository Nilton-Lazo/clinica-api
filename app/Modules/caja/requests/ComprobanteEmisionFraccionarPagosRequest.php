<?php

namespace App\Modules\caja\requests;

use Illuminate\Foundation\Http\FormRequest;

class ComprobanteEmisionFraccionarPagosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pagos' => ['required', 'array', 'size:2'],
            'pagos.*.forma_pago_id' => ['required', 'integer', 'exists:caja_formas_pago,id'],
            'pagos.*.medio_pago_id' => ['required', 'integer', 'exists:caja_medios_pago,id'],
            'pagos.*.banco_tarjeta_id' => ['nullable', 'integer', 'exists:caja_bancos_tarjetas,id'],
            'pagos.*.numero_operacion' => ['nullable', 'string', 'max:120'],
            'pagos.*.fecha_vencimiento' => ['nullable', 'date_format:Y-m-d'],
            'pagos.*.monto' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'pagos.required' => 'Debes enviar las dos líneas de pago fraccionado.',
            'pagos.size' => 'Debes enviar exactamente dos líneas de pago.',
            'pagos.*.forma_pago_id.required' => 'Selecciona la forma de pago en cada línea.',
            'pagos.*.medio_pago_id.required' => 'Selecciona el medio de pago en cada línea.',
            'pagos.*.monto.required' => 'Indica el monto de cada línea de pago.',
            'pagos.*.monto.numeric' => 'El monto de cada línea debe ser un número válido.',
            'pagos.*.monto.min' => 'El monto de cada línea no puede ser negativo.',
            'pagos.*.fecha_vencimiento.date_format' => 'La fecha de vencimiento debe tener el formato YYYY-MM-DD.',
        ];
    }
}
