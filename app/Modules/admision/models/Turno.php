<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $table = 'turnos';

    protected $fillable = [
        'codigo',
        'hora_inicio',
        'hora_fin',
        'duracion_minutos',
        'descripcion',
        'tipo_turno',
        'jornada',
        'estado',
    ];

    protected $casts = [
        'duracion_minutos' => 'integer',
    ];

    protected $appends = [
        'duracion_hhmm',
        'duracion_valor',
        'duracion_unidad',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }

    public function getHoraInicioAttribute($value): ?string
    {
        if ($value === null) return null;
        return substr((string)$value, 0, 5);
    }

    public function getHoraFinAttribute($value): ?string
    {
        if ($value === null) return null;
        return substr((string)$value, 0, 5);
    }

    public function getDuracionHhmmAttribute(): string
    {
        $m = (int)($this->duracion_minutos ?? 0);
        $h = intdiv($m, 60);
        $r = $m % 60;
        return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$r, 2, '0', STR_PAD_LEFT);
    }

    public function getDuracionValorAttribute(): int
    {
        $m = (int)($this->duracion_minutos ?? 0);

        if ($m < 60) {
            return $m;
        }

        if ($m % 60 === 0) {
            return (int)($m / 60);
        }

        return $m;
    }

    public function getDuracionUnidadAttribute(): string
    {
        $m = (int)($this->duracion_minutos ?? 0);

        if ($m < 60) {
            return 'MINUTOS';
        }

        if ($m % 60 === 0) {
            return 'HORAS';
        }

        return 'MINUTOS';
    }
}
