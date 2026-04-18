<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;

class CajaTipoDocumento extends AuditableModel
{
    protected $table = 'caja_tipos_documento';

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
