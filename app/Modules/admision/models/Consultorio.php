<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;

class Consultorio extends AuditableModel
{
    protected $table = 'consultorios';

    protected $fillable = [
        'abreviatura',
        'descripcion',
        'es_tercero',
        'estado',
    ];

    protected $casts = [
        'es_tercero' => 'boolean',
    ];

    protected $appends = [
        'es_terceros',
    ];

    public function getEsTercerosAttribute(): bool
    {
        return (bool) $this->es_tercero;
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
