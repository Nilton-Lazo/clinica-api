<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CajaFormaPago extends AuditableModel
{
    protected $table = 'caja_formas_pago';

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }

    public function mediosPago(): BelongsToMany
    {
        return $this->belongsToMany(CajaMedioPago::class, 'caja_medio_pago_forma_pago', 'forma_pago_id', 'medio_pago_id')->withTimestamps();
    }
}
