<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;

class Cliente extends AuditableModel
{
    protected $table = 'clientes';

    protected $fillable = [
        'codigo',
        'tipo',
        'nombre',
        'dni_o_ruc',
        'telefono',
        'direccion',
        'estado',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
