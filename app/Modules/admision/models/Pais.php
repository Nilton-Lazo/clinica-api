<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use App\Core\audit\AuditableModel;

class Pais extends AuditableModel
{
    protected $table = 'paises';

    protected $primaryKey = 'iso2';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'iso2',
        'nombre',
        'estado',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
