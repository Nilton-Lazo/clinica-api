<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PacientePlan extends Model
{
    protected $table = 'paciente_planes';

    protected $fillable = [
        'paciente_id',
        'tipo_cliente_id',
        'parentesco_seguro',
        'fecha_afiliacion',
        'estado',
    ];

    protected $casts = [
        'fecha_afiliacion' => 'date:Y-m-d',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function tipoCliente()
    {
        return $this->belongsTo(TipoCliente::class, 'tipo_cliente_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
