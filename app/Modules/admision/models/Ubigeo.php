<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use App\Core\audit\AuditableModel;

class Ubigeo extends AuditableModel
{
    protected $table = 'ubigeos';

    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'departamento',
        'provincia',
        'distrito',
        'estado',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
