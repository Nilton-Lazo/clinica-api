<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use App\Core\audit\AuditableModel;

class PacienteContactoEmergencia extends AuditableModel
{
    protected $table = 'paciente_contactos_emergencia';

    protected $fillable = [
        'paciente_id',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'parentesco_emergencia',
        'celular',
        'telefono',
        'observaciones',
        'estado',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
