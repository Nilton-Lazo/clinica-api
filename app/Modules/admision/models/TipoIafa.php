<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use App\Core\audit\AuditableModel;

class TipoIafa extends AuditableModel
{
    protected $table = 'tipos_iafas';

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
