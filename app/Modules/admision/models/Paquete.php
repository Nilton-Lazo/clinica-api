<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;

class Paquete extends AuditableModel
{
    protected $table = 'paquetes';

    protected $fillable = [
        'codigo',
        'descripcion',
        'tarifa_id',
        'precio_sin_igv',
        'vigencia_actual',
        'dias_hospitalizacion',
        'cuenta_contabilidad',
        'estado',
    ];

    protected $casts = [
        'precio_sin_igv' => 'decimal:4',
        'vigencia_actual' => 'date',
        'dias_hospitalizacion' => 'integer',
    ];

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function paqueteServicios()
    {
        return $this->hasMany(PaqueteServicio::class, 'paquete_id');
    }

    public function servicios()
    {
        return $this->belongsToMany(
            TarifaServicio::class,
            'paquete_servicios',
            'paquete_id',
            'tarifa_servicio_id'
        )->withTimestamps();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
