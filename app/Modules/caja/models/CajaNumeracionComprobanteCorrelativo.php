<?php

namespace App\Modules\caja\models;

use App\Core\audit\AuditableModel;
use App\Modules\admision\models\CajaNumeracionComprobante;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaNumeracionComprobanteCorrelativo extends AuditableModel
{
    protected $table = 'caja_numeracion_comprobante_correlativos';

    protected $fillable = [
        'numeracion_comprobante_id',
        'next_numero',
    ];

    protected $casts = [
        'numeracion_comprobante_id' => 'integer',
        'next_numero' => 'integer',
    ];

    public function numeracionComprobante(): BelongsTo
    {
        return $this->belongsTo(CajaNumeracionComprobante::class, 'numeracion_comprobante_id');
    }
}
