<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;

class RegistroEmergencia extends Model
{
    protected $table = 'registro_emergencia';

    protected $fillable = [
        'orden',
        'hora',
        'hora_asistencia',
        'numero_hc',
        'apellidos_nombres',
        'sexo',
        'tipo_cliente',
        'paciente_plan_id',
        'tarifa_id',
        'parentesco_seguro',
        'titular_nombre',
        'monto_a_pagar',
        'fecha',
        'cuenta',
        'medico_emergencia',
        'medico_especialista',
        'topico',
        'numero_cuenta',
        'estado',
        // New fields
        'tipo_emergencia_id',
        'topico_id',
        'medico_emergencia_id',
        'diagnostico_ingreso',
        'soat_activo',
        'soat_tipo_documento_id',
        'soat_numero_documento',
        'soat_titular_referencia',
        'soat_poliza',
        'soat_placa',
        'soat_siniestro',
        'soat_tipo_accidente',
        'soat_lugar_accidente',
        'soat_dni_conductor',
        'soat_apellido_paterno_conductor',
        'soat_apellido_materno_conductor',
        'soat_contacto_conductor',
        'soat_fecha_siniestro',
        'soat_hora_siniestro',
        'soat_datos_intervencion_autoridad',
        'soat_documento_atencion_id_1',
        'soat_numero_documento_atencion_1',
        'soat_documento_atencion_id_2',
        'soat_numero_documento_atencion_2',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_a_pagar' => 'decimal:2',
        'soat_activo' => 'boolean',
        'soat_fecha_siniestro' => 'date',
    ];

    public function pacientePlan()
    {
        return $this->belongsTo(PacientePlan::class, 'paciente_plan_id');
    }

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function servicios()
    {
        return $this->hasMany(RegistroEmergenciaServicio::class, 'registro_emergencia_id');
    }

    public function tipoEmergencia()
    {
        return $this->belongsTo(TipoEmergencia::class, 'tipo_emergencia_id');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'numero_hc', 'numero_documento');
    }
}

