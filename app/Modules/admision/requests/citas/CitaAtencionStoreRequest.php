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
            'servicios.*.cop_var' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'servicios.*.cop_fijo' => ['sometimes', 'numeric', 'min:0'],
            'servicios.*.descuento_pct' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'servicios.*.aumento_pct' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'servicios.*.cantidad' => ['sometimes', 'numeric', 'min:0.001'],
            'servicios.*.precio_sin_igv' => ['required', 'numeric', 'min:0'],
            'servicios.*.precio_con_igv' => ['required', 'numeric', 'min:0'],
            'servicios.*.estado_facturacion' => ['sometimes', 'string', Rule::in(EstadoFacturacionServicio::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'solo_actualizar_datos.boolean' => 'El indicador de actualización parcial no es válido.',
            'acudio_a_su_cita.boolean' => 'El indicador de asistencia a la cita no es válido.',
            'hora_asistencia.regex' => 'La hora de atención debe tener el formato HH:mm.',
            'paciente_plan_id.integer' => 'El plan del paciente seleccionado no es válido.',
            'paciente_plan_id.exists' => 'El plan del paciente seleccionado no existe.',
            'parentesco_seguro.string' => 'La condición del seguro debe ser texto.',
            'parentesco_seguro.max' => 'La condición del seguro no debe superar 30 caracteres.',
            'titular_nombre.string' => 'El titular del seguro debe ser texto.',
            'titular_nombre.max' => 'El titular del seguro no debe superar 255 caracteres.',
            'control_pre_post_natal.boolean' => 'El indicador de control pre/post natal no es válido.',
            'control_nino_sano.boolean' => 'El indicador de control de niño sano no es válido.',
            'chequeo.boolean' => 'El indicador de chequeo no es válido.',
            'carencia.boolean' => 'El indicador de carencia no es válido.',
            'latencia.boolean' => 'El indicador de latencia no es válido.',
            'monto_a_pagar.numeric' => 'El monto a pagar debe ser numérico.',
            'monto_a_pagar.min' => 'El monto a pagar no puede ser negativo.',
            'soat_activo.boolean' => 'El indicador SOAT no es válido.',
            'soat_numero_poliza.string' => 'El número de póliza SOAT debe ser texto.',
            'soat_numero_poliza.max' => 'El número de póliza SOAT no debe superar 50 caracteres.',
            'soat_numero_placa.string' => 'El número de placa SOAT debe ser texto.',
            'soat_numero_placa.max' => 'El número de placa SOAT no debe superar 20 caracteres.',
            'servicios.array' => 'La lista de servicios de la atención tiene un formato inválido.',
            'servicios.*.tarifa_servicio_id.required' => 'Cada servicio debe indicar el ítem del tarifario.',
            'servicios.*.tarifa_servicio_id.integer' => 'El servicio del tarifario seleccionado no es válido.',
            'servicios.*.tarifa_servicio_id.exists' => 'Uno de los servicios del tarifario no existe.',
            'servicios.*.medico_id.required' => 'Cada servicio debe indicar el médico responsable.',
            'servicios.*.medico_id.integer' => 'El médico responsable de un servicio no es válido.',
            'servicios.*.medico_id.exists' => 'El médico responsable de un servicio no existe.',
            'servicios.*.cop_var.numeric' => 'El copago variable de un servicio debe ser numérico.',
            'servicios.*.cop_var.min' => 'El copago variable de un servicio no puede ser negativo.',
            'servicios.*.cop_var.max' => 'El copago variable de un servicio no puede superar 100%.',
            'servicios.*.cop_fijo.numeric' => 'El copago fijo de un servicio debe ser numérico.',
            'servicios.*.cop_fijo.min' => 'El copago fijo de un servicio no puede ser negativo.',
            'servicios.*.descuento_pct.numeric' => 'El descuento de un servicio debe ser numérico.',
            'servicios.*.descuento_pct.min' => 'El descuento de un servicio no puede ser negativo.',
            'servicios.*.descuento_pct.max' => 'El descuento de un servicio no puede superar 100%.',
            'servicios.*.aumento_pct.numeric' => 'El aumento de un servicio debe ser numérico.',
            'servicios.*.aumento_pct.min' => 'El aumento de un servicio no puede ser negativo.',
            'servicios.*.aumento_pct.max' => 'El aumento de un servicio no puede superar 100%.',
            'servicios.*.cantidad.numeric' => 'La cantidad de un servicio debe ser numérica.',
            'servicios.*.cantidad.min' => 'La cantidad de un servicio debe ser mayor que cero.',
            'servicios.*.precio_sin_igv.required' => 'Cada servicio debe indicar el precio sin IGV.',
            'servicios.*.precio_sin_igv.numeric' => 'El precio sin IGV de un servicio debe ser numérico.',
            'servicios.*.precio_sin_igv.min' => 'El precio sin IGV de un servicio no puede ser negativo.',
            'servicios.*.precio_con_igv.required' => 'Cada servicio debe indicar el precio con IGV.',
            'servicios.*.precio_con_igv.numeric' => 'El precio con IGV de un servicio debe ser numérico.',
            'servicios.*.precio_con_igv.min' => 'El precio con IGV de un servicio no puede ser negativo.',
            'servicios.*.estado_facturacion.string' => 'El estado de facturación de un servicio debe ser texto.',
            'servicios.*.estado_facturacion.in' => 'El estado de facturación de un servicio no es válido.',
        ];
    }
}
