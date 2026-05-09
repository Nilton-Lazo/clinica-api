<?php

namespace App\Modules\caja\models;

use App\Core\audit\AuditableModel;
use App\Models\User;
use App\Modules\admision\models\AreaJefatura;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaApertura extends AuditableModel
{
    protected $table = 'caja_aperturas';

    public const TIPO_NORMAL = 'NORMAL';

    public const TIPO_CHICA = 'CHICA';

    protected $fillable = [
        'codigo',
        'tipo',
        'user_entrega_id',
        'user_recepciona_id',
        'area_jefatura_id',
        'moneda',
        'monto_inicio',
        'monto_cierre',
        'ajuste_cierre',
        'usuario_caja',
        'observaciones',
        'observaciones_cierre',
        'apertura_at',
        'cerrada_at',
    ];

    protected $casts = [
        'monto_inicio' => 'decimal:2',
        'monto_cierre' => 'decimal:2',
        'ajuste_cierre' => 'decimal:3',
        'apertura_at' => 'datetime',
        'cerrada_at' => 'datetime',
    ];

    public function userEntrega(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_entrega_id');
    }

    public function userRecepciona(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_recepciona_id');
    }

    public function areaJefatura(): BelongsTo
    {
        return $this->belongsTo(AreaJefatura::class, 'area_jefatura_id');
    }
}
