<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;

class ServicioDefaultEmergencia extends Model
{
    protected $table = 'ficheros_emergencia_servicios_defaults';

    protected $fillable = [
        'tarifa_id',
        'codigo_servicio',
    ];

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }
}
