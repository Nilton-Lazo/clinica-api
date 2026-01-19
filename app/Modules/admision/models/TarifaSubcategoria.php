<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TarifaSubcategoria extends Model
{
    protected $table = 'tarifa_subcategorias';

    protected $fillable = [
        'tarifa_id',
        'categoria_id',
        'codigo',
        'nombre',
        'estado',
    ];

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function categoria()
    {
        return $this->belongsTo(TarifaCategoria::class, 'categoria_id');
    }

    public function servicios()
    {
        return $this->hasMany(TarifaServicio::class, 'subcategoria_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
