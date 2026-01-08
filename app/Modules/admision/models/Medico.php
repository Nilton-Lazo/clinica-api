<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medico extends Model
{
    protected $table = 'medicos';

    protected $fillable = [
        'codigo',
        'cmp',
        'rne',
        'dni',
        'tipo_profesional_clinica',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'direccion',
        'centro_trabajo',
        'fecha_nacimiento',
        'ruc',
        'especialidad_id',
        'telefono',
        'telefono_02',
        'email',
        'adicionales',
        'extras',
        'tiempo_promedio_por_paciente',
        'estado',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'adicionales' => 'integer',
        'extras' => 'integer',
        'tiempo_promedio_por_paciente' => 'integer',
    ];

    protected $appends = ['nombre_completo'];

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        $n = trim((string)($this->nombres ?? ''));
        $ap = trim((string)($this->apellido_paterno ?? ''));
        $am = trim((string)($this->apellido_materno ?? ''));

        return trim($ap . ' ' . $am . ', ' . $n);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
