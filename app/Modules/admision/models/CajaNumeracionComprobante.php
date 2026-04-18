<?php

namespace App\Modules\admision\models;

use App\Core\audit\AuditableModel;
use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaNumeracionComprobante extends AuditableModel
{
    protected $table = 'caja_numeraciones_comprobante';

    protected $fillable = [
        'tipo_documento_id',
        'serie',
        'numero',
        'estado',
    ];

    protected $casts = [
        'tipo_documento_id' => 'integer',
        'numero' => 'integer',
    ];

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(CajaTipoDocumento::class, 'tipo_documento_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
