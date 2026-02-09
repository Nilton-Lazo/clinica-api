<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;

class CitaAtencionServicio extends Model
{
    protected $table = 'cita_atencion_servicios';

    protected $fillable = [
        'cita_atencion_id',
        'tarifa_servicio_id',
        'medico_id',
        'user_id',
        'cop_var',
        'cop_fijo',
        'descuento_pct',
        'aumento_pct',
        'cantidad',
        'precio_sin_igv',
        'precio_con_igv',
        'estado_facturacion',
    ];

    protected $casts = [
        'cop_var' => 'decimal:3',
        'cop_fijo' => 'decimal:3',
        'descuento_pct' => 'decimal:3',
        'aumento_pct' => 'decimal:3',
        'cantidad' => 'decimal:3',
        'precio_sin_igv' => 'decimal:4',
        'precio_con_igv' => 'decimal:4',
    ];

    public function citaAtencion()
    {
        return $this->belongsTo(CitaAtencion::class, 'cita_atencion_id');
    }

    public function tarifaServicio()
    {
        return $this->belongsTo(TarifaServicio::class, 'tarifa_servicio_id');
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
