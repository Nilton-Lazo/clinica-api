<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Iafa extends Model
{
    protected $table = 'iafas';

    protected $fillable = [
        'codigo',
        'tipo_iafa_id',
        'razon_social',
        'descripcion_corta',
        'ruc',
        'direccion',
        'representante_legal',
        'telefono',
        'pagina_web',
        'fecha_inicio_cobertura',
        'fecha_fin_cobertura',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio_cobertura' => 'date:Y-m-d',
        'fecha_fin_cobertura' => 'date:Y-m-d',
    ];

    public function tipo()
    {
        return $this->belongsTo(TipoIafa::class, 'tipo_iafa_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
