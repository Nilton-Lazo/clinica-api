<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nr',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'estado_civil',
        'sexo',
        'fecha_nacimiento',
        'nacionalidad_iso2',
        'ubigeo_nacimiento',
        'direccion',
        'ubigeo_domicilio',
        'parentesco_seguro',
        'titular_nombre',
        'celular',
        'telefono',
        'medico_tratante_id',
        'tipo_sangre',
        'tipo_paciente',
        'ocupacion',
        'email',
        'medio_informacion',
        'medio_informacion_detalle',
        'ubicacion_archivo_hc',
        'estado',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date:Y-m-d',
    ];

    protected $appends = [
        'hc',
        'nombre_completo',
        'edad',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }

    public function paisNacionalidad()
    {
        return $this->belongsTo(Pais::class, 'nacionalidad_iso2', 'iso2');
    }

    public function ubigeoNacimiento()
    {
        return $this->belongsTo(Ubigeo::class, 'ubigeo_nacimiento', 'codigo');
    }

    public function ubigeoDomicilio()
    {
        return $this->belongsTo(Ubigeo::class, 'ubigeo_domicilio', 'codigo');
    }

    public function contactoEmergencia()
    {
        return $this->hasOne(PacienteContactoEmergencia::class, 'paciente_id');
    }

    public function planes()
    {
        return $this->hasMany(PacientePlan::class, 'paciente_id')->orderBy('id', 'asc');
    }

    public function getHcAttribute(): string
    {
        $doc = trim((string)($this->numero_documento ?? ''));
        if ($doc !== '') {
            return $doc;
        }

        return (string)($this->nr ?? '');
    }

    public function getNombreCompletoAttribute(): string
    {
        $a = trim((string)($this->apellido_paterno ?? ''));
        $b = trim((string)($this->apellido_materno ?? ''));
        $n = trim((string)($this->nombres ?? ''));

        $x = trim(($a . ' ' . $b . ' ' . $n));
        return $x;
    }

    public function getEdadAttribute(): ?int
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }

        $d = $this->fecha_nacimiento;
        $now = now()->startOfDay();
        $age = $d->diffInYears($now);

        return $age >= 0 ? $age : null;
    }
}
