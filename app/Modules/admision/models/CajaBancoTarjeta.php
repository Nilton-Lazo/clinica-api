<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CajaBancoTarjeta extends AuditableModel
{
    protected $table = 'caja_bancos_tarjetas';

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado',
    ];

    public function formasPago(): BelongsToMany
    {
        return $this->belongsToMany(CajaFormaPago::class, 'caja_banco_tarjeta_forma_pago', 'banco_tarjeta_id', 'forma_pago_id')->withTimestamps();
    }

    public function mediosPago(): BelongsToMany
    {
        return $this->belongsToMany(CajaMedioPago::class, 'caja_banco_tarjeta_medio_pago', 'banco_tarjeta_id', 'medio_pago_id')->withTimestamps();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
