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
    ];

    protected $casts = [
        'control_pre_post_natal' => 'boolean',
        'control_nino_sano' => 'boolean',
        'chequeo' => 'boolean',
        'carencia' => 'boolean',
        'latencia' => 'boolean',
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
}
