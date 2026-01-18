<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Contratante extends Model
{
    protected $table = 'contratantes';

    protected $fillable = [
        'codigo',
        'razon_social',
        'ruc',
        'telefono',
        'direccion',
        'estado',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
