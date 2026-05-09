<?php

namespace App\Modules\caja\models;

use App\Core\audit\AuditableModel;
use App\Modules\admision\models\CajaBancoTarjeta;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\admision\models\CajaMedioPago;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaEmisionComprobantePago extends AuditableModel
{
    protected $table = 'caja_emision_comprobante_pagos';

    protected $fillable = [
        'emision_comprobante_id',
        'forma_pago_id',
        'medio_pago_id',
        'banco_tarjeta_id',
        'numero_operacion',
        'monto',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'emision_comprobante_id' => 'integer',
        'forma_pago_id' => 'integer',
        'medio_pago_id' => 'integer',
        'banco_tarjeta_id' => 'integer',
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'date:Y-m-d',
    ];

    public function emisionComprobante(): BelongsTo
    {
        return $this->belongsTo(CajaEmisionComprobante::class, 'emision_comprobante_id');
    }

    public function formaPago(): BelongsTo
    {
        return $this->belongsTo(CajaFormaPago::class, 'forma_pago_id');
    }

    public function medioPago(): BelongsTo
    {
        return $this->belongsTo(CajaMedioPago::class, 'medio_pago_id');
    }

    public function bancoTarjeta(): BelongsTo
    {
        return $this->belongsTo(CajaBancoTarjeta::class, 'banco_tarjeta_id');
    }
}
