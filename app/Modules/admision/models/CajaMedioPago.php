<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CajaMedioPago extends AuditableModel
{
    protected $table = 'caja_medios_pago';

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado',
    ];

    public function formasPago(): BelongsToMany
    {
        return $this->belongsToMany(CajaFormaPago::class, 'caja_medio_pago_forma_pago', 'medio_pago_id', 'forma_pago_id')->withTimestamps();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
