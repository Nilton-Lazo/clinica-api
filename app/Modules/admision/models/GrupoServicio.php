<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;

class GrupoServicio extends Model
{
    protected $table = 'grupos_servicio';

    protected $fillable = ['codigo', 'descripcion', 'abrev', 'orden', 'estado'];

    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO');
    }
}
