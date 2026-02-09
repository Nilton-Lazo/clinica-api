<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Model;

class TarifaRecargoNoche extends Model
{
    protected $table = 'tarifa_recargo_noche';

    protected $fillable = [
        'tarifa_id',
        'tarifa_categoria_id',
        'porcentaje',
        'hora_desde',
        'hora_hasta',
        'estado',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
    ];

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function tarifaCategoria()
    {
        return $this->belongsTo(TarifaCategoria::class, 'tarifa_categoria_id');
    }
}
