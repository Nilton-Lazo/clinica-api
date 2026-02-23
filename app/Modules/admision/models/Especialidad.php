<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Especialidad extends AuditableModel
{
    protected $table = 'especialidades';

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado',
    ];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
