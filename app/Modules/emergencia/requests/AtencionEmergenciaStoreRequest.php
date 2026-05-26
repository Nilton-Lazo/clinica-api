<?php

namespace App\Modules\emergencia\requests;

use Illuminate\Foundation\Http\FormRequest;

class AtencionEmergenciaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'servicios.*.cop_var' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'servicios.*.cop_fijo' => ['nullable', 'numeric', 'min:0'],
            'servicios.*.descuento_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'servicios.*.aumento_pct' => ['nullable', 'numeric', 'min:0'],
            'servicios.*.cantidad' => ['nullable', 'numeric', 'min:0.001'],
            'servicios.*.precio_sin_igv' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.precio_con_igv' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.estado_facturacion' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'acudio_a_su_cita.boolean' => 'El indicador de asistencia debe ser verdadero o falso.',
            'hora_asistencia.date_format' => 'La hora de asistencia debe tener el formato HH:MM.',
            'paciente_plan_id.integer' => 'Selecciona un tipo de cliente válido para la atención de emergencia.',
            'paciente_plan_id.exists' => 'El tipo de cliente seleccionado no existe o ya no está disponible para el paciente.',
            'parentesco_seguro.string' => 'La condición del paciente debe ser texto.',
            'parentesco_seguro.max' => 'La condición del paciente no debe superar 30 caracteres.',
            'titular_nombre.string' => 'El titular del paciente debe ser texto.',
            'titular_nombre.max' => 'El titular del paciente no debe superar 255 caracteres.',
            'monto_a_pagar.numeric' => 'El monto a pagar debe ser numérico.',
            'monto_a_pagar.min' => 'El monto a pagar no puede ser negativo.',
            'servicios.array' => 'La lista de servicios finales debe tener un formato válido.',
            'servicios.*.tarifa_servicio_id.required_with' => 'Cada servicio final debe tener un servicio de tarifario seleccionado.',
            'servicios.*.tarifa_servicio_id.integer' => 'Cada servicio final debe tener un tarifario válido.',
            'servicios.*.tarifa_servicio_id.exists' => 'Uno de los servicios finales ya no existe en el tarifario.',
            'servicios.*.medico_id.required_with' => 'Cada servicio final debe tener un médico asignado.',
            'servicios.*.medico_id.integer' => 'Cada servicio final debe tener un médico válido.',
            'servicios.*.medico_id.exists' => 'Uno de los médicos asignados a servicios finales no existe.',
            'servicios.*.cop_var.numeric' => 'El copago variable de cada servicio final debe ser numérico.',
            'servicios.*.cop_var.min' => 'El copago variable de cada servicio final no puede ser negativo.',
            'servicios.*.cop_var.max' => 'El copago variable de cada servicio final no puede superar 100%.',
            'servicios.*.cop_fijo.numeric' => 'El copago fijo de cada servicio final debe ser numérico.',
            'servicios.*.cop_fijo.min' => 'El copago fijo de cada servicio final no puede ser negativo.',
            'servicios.*.descuento_pct.numeric' => 'El descuento de cada servicio final debe ser numérico.',
            'servicios.*.descuento_pct.min' => 'El descuento de cada servicio final no puede ser negativo.',
            'servicios.*.descuento_pct.max' => 'El descuento de cada servicio final no puede superar el 100%.',
            'servicios.*.aumento_pct.numeric' => 'El aumento de cada servicio final debe ser numérico.',
            'servicios.*.aumento_pct.min' => 'El aumento de cada servicio final no puede ser negativo.',
            'servicios.*.cantidad.numeric' => 'La cantidad de cada servicio final debe ser numérica.',
            'servicios.*.cantidad.min' => 'La cantidad de cada servicio final debe ser mayor que cero.',
            'servicios.*.precio_sin_igv.required_with' => 'Cada servicio final debe tener precio sin IGV.',
            'servicios.*.precio_sin_igv.numeric' => 'El precio sin IGV de cada servicio final debe ser numérico.',
            'servicios.*.precio_sin_igv.min' => 'El precio sin IGV de cada servicio final no puede ser negativo.',
            'servicios.*.precio_con_igv.required_with' => 'Cada servicio final debe tener precio con IGV.',
            'servicios.*.precio_con_igv.numeric' => 'El precio con IGV de cada servicio final debe ser numérico.',
            'servicios.*.precio_con_igv.min' => 'El precio con IGV de cada servicio final no puede ser negativo.',
            'servicios.*.estado_facturacion.string' => 'El estado de facturación de cada servicio final debe ser texto.',
            'servicios.*.estado_facturacion.max' => 'El estado de facturación de cada servicio final no debe superar 20 caracteres.',
        ];
    }
}
