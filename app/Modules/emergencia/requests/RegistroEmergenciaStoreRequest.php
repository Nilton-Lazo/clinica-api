<?php

namespace App\Modules\emergencia\requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistroEmergenciaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'orden' => ['nullable', 'string', 'max:50'],
            'hora' => ['nullable', 'string', 'max:20'],
            'numero_hc' => ['required', 'string', 'max:50'],
            'apellidos_nombres' => ['required', 'string', 'max:255'],
            'sexo' => ['nullable', 'string', 'max:20'],
            'tipo_cliente' => ['nullable', 'string', 'max:100'],
            'fecha' => ['required', 'date'],
            'cuenta' => ['nullable', 'string', 'max:100'],
            'medico_emergencia' => ['nullable', 'string', 'max:255'],
            'medico_especialista' => ['nullable', 'string', 'max:255'],
            'topico' => ['nullable', 'string', 'max:100'],
            'numero_cuenta' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:20'],
            'tipo_emergencia_id' => ['nullable', 'integer'],
            'topico_id' => ['nullable', 'integer'],
            'medico_emergencia_id' => ['nullable', 'integer'],
            'diagnostico_ingreso' => ['nullable', 'string', 'max:500'],
            'soat_activo' => ['nullable', 'boolean'],
            'soat_tipo_documento_id' => ['nullable', 'integer'],
            'soat_numero_documento' => ['nullable', 'string', 'max:100'],
            'soat_titular_referencia' => ['nullable', 'string', 'max:255'],
            'soat_poliza' => ['nullable', 'string', 'max:100'],
            'soat_placa' => ['nullable', 'string', 'max:50'],
            'soat_siniestro' => ['nullable', 'string', 'max:255'],
            'soat_tipo_accidente' => ['nullable', 'string', 'max:255'],
            'soat_lugar_accidente' => ['nullable', 'string', 'max:255'],
            'soat_dni_conductor' => ['nullable', 'string', 'max:50'],
            'soat_apellido_paterno_conductor' => ['nullable', 'string', 'max:255'],
            'soat_apellido_materno_conductor' => ['nullable', 'string', 'max:255'],
            'soat_contacto_conductor' => ['nullable', 'string', 'max:100'],
            'soat_fecha_siniestro' => ['nullable', 'date'],
            'soat_hora_siniestro' => ['nullable', 'string', 'max:20'],
            'soat_datos_intervencion_autoridad' => ['nullable', 'string', 'max:500'],
            'soat_documento_atencion_id_1' => ['nullable', 'integer'],
            'soat_numero_documento_atencion_1' => ['nullable', 'string', 'max:100'],
            'soat_documento_atencion_id_2' => ['nullable', 'integer'],
            'soat_numero_documento_atencion_2' => ['nullable', 'string', 'max:100'],
        ];
    }
}
