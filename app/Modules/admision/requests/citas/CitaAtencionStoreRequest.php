<?php

namespace App\Modules\admision\requests\citas;

use App\Core\support\EstadoFacturacionServicio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CitaAtencionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'solo_actualizar_datos' => ['sometimes', 'boolean'],
            'acudio_a_su_cita' => ['sometimes', 'boolean'],
            'hora_asistencia' => ['nullable', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'paciente_plan_id' => ['nullable', 'integer', 'exists:paciente_planes,id'],
            'parentesco_seguro' => ['nullable', 'string', 'max:30'],
            'titular_nombre' => ['nullable', 'string', 'max:255'],
            'control_pre_post_natal' => ['sometimes', 'boolean'],
            'control_nino_sano' => ['sometimes', 'boolean'],
            'chequeo' => ['sometimes', 'boolean'],
            'carencia' => ['sometimes', 'boolean'],
            'latencia' => ['sometimes', 'boolean'],
            'monto_a_pagar' => ['sometimes', 'numeric', 'min:0'],
            'soat_activo' => ['sometimes', 'boolean'],
            'soat_numero_poliza' => ['nullable', 'string', 'max:50'],
            'soat_numero_placa' => ['nullable', 'string', 'max:20'],

            'servicios' => ['sometimes', 'array'],
            'servicios.*.tarifa_servicio_id' => ['required', 'integer', 'exists:tarifa_servicios,id'],
            'servicios.*.medico_id' => ['required', 'integer', 'exists:medicos,id'],
            'servicios.*.cop_var' => ['sometimes', 'numeric', 'min:0'],
            'servicios.*.cop_fijo' => ['sometimes', 'numeric', 'min:0'],
            'servicios.*.descuento_pct' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'servicios.*.aumento_pct' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'servicios.*.cantidad' => ['sometimes', 'numeric', 'min:0.001'],
            'servicios.*.precio_sin_igv' => ['required', 'numeric', 'min:0'],
            'servicios.*.precio_con_igv' => ['required', 'numeric', 'min:0'],
            'servicios.*.estado_facturacion' => ['sometimes', 'string', Rule::in(EstadoFacturacionServicio::values())],
        ];
    }
}
