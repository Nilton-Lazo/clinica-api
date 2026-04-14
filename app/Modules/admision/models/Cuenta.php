<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cuenta extends Model
{
    protected $table = 'cuentas';

    protected $fillable = [
        'nro_cuenta',
        'origen',
        'origen_id',
        'paciente_id',
        'paciente_plan_id',
        'tarifa_id',
        'fecha',
        'estado',
        'paciente_nombre',
        'hc',
        'nr',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}
