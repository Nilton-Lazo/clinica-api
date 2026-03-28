<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;

class PaqueteServicio extends AuditableModel
{
    protected $table = 'paquete_servicios';

    protected $fillable = [
        'paquete_id',
        'tarifa_servicio_id',
    ];

    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    public function tarifaServicio()
    {
        return $this->belongsTo(TarifaServicio::class, 'tarifa_servicio_id');
    }
}
