<?php

namespace App\Modules\emergencia\requests;

use Illuminate\Foundation\Http\FormRequest;

class AtencionEmergenciaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorizations can be handled in Controller or Policy
    }

    public function rules(): array
    {
        return [
            'acudio_a_su_cita' => ['nullable', 'boolean'],
            'hora_asistencia' => ['nullable', 'date_format:H:i'],
            'paciente_plan_id' => ['nullable', 'integer', 'exists:paciente_planes,id'],
            'parentesco_seguro' => ['nullable', 'string', 'max:30'],
            'titular_nombre' => ['nullable', 'string', 'max:255'],
            'monto_a_pagar' => ['nullable', 'numeric', 'min:0'],

            'servicios' => ['nullable', 'array'],
            'servicios.*.tarifa_servicio_id' => ['required_with:servicios', 'integer', 'exists:tarifa_servicios,id'],
            'servicios.*.medico_id' => ['required_with:servicios', 'integer', 'exists:medicos,id'],
            'servicios.*.cop_var' => ['nullable', 'numeric'],
            'servicios.*.cop_fijo' => ['nullable', 'numeric'],
            'servicios.*.descuento_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'servicios.*.aumento_pct' => ['nullable', 'numeric', 'min:0'],
            'servicios.*.cantidad' => ['nullable', 'numeric', 'min:0.001'],
            'servicios.*.precio_sin_igv' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.precio_con_igv' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.estado_facturacion' => ['nullable', 'string', 'max:20'],
        ];
    }
}
