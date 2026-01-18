<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TipoCliente extends Model
{
    protected $table = 'tipos_clientes';

    protected $fillable = [
        'codigo',
        'tarifa_id',
        'iafa_id',
        'contratante_id',
        'descripcion_tipo_cliente',
        'estado',
    ];

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function iafa()
    {
        return $this->belongsTo(Iafa::class, 'iafa_id');
    }

    public function contratante()
    {
        return $this->belongsTo(Contratante::class, 'contratante_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
