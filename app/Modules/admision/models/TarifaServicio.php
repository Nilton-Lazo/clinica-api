<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TarifaServicio extends Model
{
    protected $table = 'tarifa_servicios';

    protected $fillable = [
        'tarifa_id',
        'categoria_id',
        'subcategoria_id',
        'servicio_codigo',
        'codigo',
        'nomenclador',
        'descripcion',
        'precio_sin_igv',
        'unidad',
        'estado',
    ];

    protected $casts = [
        'precio_sin_igv' => 'decimal:4',
        'unidad' => 'decimal:4',
    ];

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function categoria()
    {
        return $this->belongsTo(TarifaCategoria::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(TarifaSubcategoria::class, 'subcategoria_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
