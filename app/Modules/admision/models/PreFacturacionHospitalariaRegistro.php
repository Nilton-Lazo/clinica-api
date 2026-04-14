<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreFacturacionHospitalariaRegistro extends Model
{
    protected $table = 'pre_facturacion_hospitalaria_registros';

    protected $fillable = [
        'nro_cuenta',
        'paciente_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}
