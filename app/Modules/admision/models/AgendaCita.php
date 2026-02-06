<?php

namespace App\Modules\admision\models;

use App\Core\support\SexoPaciente;
use Illuminate\Database\Eloquent\Model;

class AgendaCita extends Model
{
    protected $table = 'agenda_citas';

    protected $fillable = [
        'codigo',
        'programacion_medica_id',
        'fecha',
        'hora',
        'orden',
        'paciente_id',
        'hc',
        'nr',
        'paciente_nombre',
        'sexo',
        'edad',
        'titular_nombre',
        'cuenta',
        'iafa_id',
        'motivo',
        'observacion',
        'autorizacion_siteds',
        'estado',
        'estado_atencion',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
    ];

    public function programacion()
    {
        return $this->belongsTo(ProgramacionMedica::class, 'programacion_medica_id');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function iafa()
    {
        return $this->belongsTo(Iafa::class, 'iafa_id');
    }

    public function toArray(): array
    {
        $a = parent::toArray();
        if (array_key_exists('sexo', $a) && $a['sexo'] !== null) {
            $a['sexo'] = SexoPaciente::formatForDisplay($a['sexo']);
        }
        return $a;
    }
}
