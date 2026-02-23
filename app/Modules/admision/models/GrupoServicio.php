<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;

class GrupoServicio extends AuditableModel
{
    protected $table = 'grupos_servicio';

    protected $fillable = ['codigo', 'descripcion', 'abrev', 'orden', 'estado'];

    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO');
    }
}
