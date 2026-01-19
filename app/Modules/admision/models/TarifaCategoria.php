<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TarifaCategoria extends Model
{
    protected $table = 'tarifa_categorias';

    protected $fillable = [
        'tarifa_id',
        'codigo',
        'nombre',
        'estado',
    ];

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function subcategorias()
    {
        return $this->hasMany(TarifaSubcategoria::class, 'categoria_id');
    }

    public function servicios()
    {
        return $this->hasMany(TarifaServicio::class, 'categoria_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
