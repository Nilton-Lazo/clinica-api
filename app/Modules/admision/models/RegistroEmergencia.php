<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;

class RegistroEmergencia extends Model
{
    protected $table = 'registro_emergencia';

    protected $fillable = [
        'orden',
        'hora',
        'numero_hc',
        'apellidos_nombres',
        'sexo',
        'tipo_cliente',
        'fecha',
        'cuenta',
        'medico_emergencia',
        'medico_especialista',
        'topico',
        'numero_cuenta',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
