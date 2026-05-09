<?php

namespace App\Modules\caja\models;

use App\Core\audit\AuditableModel;
use App\Models\User;
use App\Modules\admision\models\CajaNumeracionComprobante;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaEmisionComprobante extends AuditableModel
{
    protected $table = 'caja_emision_comprobantes';

    protected $fillable = [
        'user_id',
        'caja_apertura_id',
        'nro_cuenta',
        'cuenta_origen',
        'numeracion_comprobante_id',
        'serie',
        'numero_emitido',
        'numero_operacion',
        'fecha_vencimiento',
        'total_paciente',
        'total_lineas',
        'snapshot',
    ];

    protected $casts = [
        'numeracion_comprobante_id' => 'integer',
        'numero_emitido' => 'integer',
        'fecha_vencimiento' => 'date:Y-m-d',
        'total_paciente' => 'decimal:2',
        'total_lineas' => 'integer',
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

    public function numeracionComprobante(): BelongsTo
    {
        return $this->belongsTo(CajaNumeracionComprobante::class, 'numeracion_comprobante_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(CajaEmisionComprobantePago::class, 'emision_comprobante_id');
    }
}
