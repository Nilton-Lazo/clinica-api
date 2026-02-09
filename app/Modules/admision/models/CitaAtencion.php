<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;

class CitaAtencion extends Model
{
    protected $table = 'cita_atenciones';

    protected $fillable = [
        'agenda_cita_id',
        'nro_cuenta',
        'hora_asistencia',
        'paciente_plan_id',
        'tarifa_id',
        'parentesco_seguro',
        'titular_nombre',
        'control_pre_post_natal',
        'control_nino_sano',
        'chequeo',
        'carencia',
        'latencia',
        'monto_a_pagar',
        'soat_activo',
        'soat_numero_poliza',
        'soat_numero_placa',
    ];

    protected $casts = [
        'control_pre_post_natal' => 'boolean',
        'control_nino_sano' => 'boolean',
        'chequeo' => 'boolean',
        'carencia' => 'boolean',
        'latencia' => 'boolean',
        'monto_a_pagar' => 'decimal:4',
        'soat_activo' => 'boolean',
    ];


    public function agendaCita()
    {
        return $this->belongsTo(AgendaCita::class, 'agenda_cita_id');
    }

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
        return $this->hasMany(CitaAtencionServicio::class, 'cita_atencion_id');
    }
}
