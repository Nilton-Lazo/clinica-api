<?php

namespace App\Modules\caja\models;

use App\Core\audit\AuditableModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaEmisionComprobante extends AuditableModel
{
    protected $table = 'caja_emision_comprobantes';

    protected $fillable = [
        'user_id',
        'caja_apertura_id',
        'nro_cuenta',
        'cuenta_origen',
        'numero_operacion',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cajaApertura(): BelongsTo
    {
        return $this->belongsTo(CajaApertura::class, 'caja_apertura_id');
    }
}
