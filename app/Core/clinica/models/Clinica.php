<?php

namespace App\Core\clinica\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;

class Clinica extends AuditableModel
{
    protected $table = 'clinica';

    protected $fillable = [
        'razon_social',
        'ruc',
        'direccion',
        'telefono',
        'email',
        'sitio_web',
        'logo_path',
        'estado',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
