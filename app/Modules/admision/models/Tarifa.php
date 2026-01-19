<?php

namespace App\Modules\admision\models;

use App\Core\support\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    protected $table = 'tarifas';

    protected $fillable = [
        'codigo',
        'requiere_acreditacion',
        'tarifa_base',
        'descripcion_tarifa',
        'iafa_id',
        'factor_clinica',
        'factor_laboratorio',
        'factor_ecografia',
        'factor_procedimientos',
        'factor_rayos_x',
        'factor_tomografia',
        'factor_patologia',
        'factor_medicina_fisica',
        'factor_resonancia',
        'factor_honorarios_medicos',
        'factor_medicinas',
        'factor_equipos_oxigeno',
        'factor_banco_sangre',
        'factor_mamografia',
        'factor_densitometria',
        'factor_psicoprofilaxis',
        'factor_otros_servicios',
        'factor_medicamentos_comerciales',
        'factor_medicamentos_genericos',
        'factor_material_medico',
        'fecha_creacion',
        'estado',
    ];

    protected $casts = [
        'requiere_acreditacion' => 'boolean',
        'tarifa_base' => 'boolean',
        'fecha_creacion' => 'date:Y-m-d',
        'factor_clinica' => 'decimal:2',
        'factor_laboratorio' => 'decimal:2',
        'factor_ecografia' => 'decimal:2',
        'factor_procedimientos' => 'decimal:2',
        'factor_rayos_x' => 'decimal:2',
        'factor_tomografia' => 'decimal:2',
        'factor_patologia' => 'decimal:2',
        'factor_medicina_fisica' => 'decimal:2',
        'factor_resonancia' => 'decimal:2',
        'factor_honorarios_medicos' => 'decimal:2',
        'factor_medicinas' => 'decimal:2',
        'factor_equipos_oxigeno' => 'decimal:2',
        'factor_banco_sangre' => 'decimal:2',
        'factor_mamografia' => 'decimal:2',
        'factor_densitometria' => 'decimal:2',
        'factor_psicoprofilaxis' => 'decimal:2',
        'factor_otros_servicios' => 'decimal:2',
        'factor_medicamentos_comerciales' => 'decimal:2',
        'factor_medicamentos_genericos' => 'decimal:2',
        'factor_material_medico' => 'decimal:2',
    ];

    public function iafa()
    {
        return $this->belongsTo(Iafa::class, 'iafa_id');
    }

    public function categorias()
    {
        return $this->hasMany(TarifaCategoria::class, 'tarifa_id');
    }

    public function subcategorias()
    {
        return $this->hasMany(TarifaSubcategoria::class, 'tarifa_id');
    }

    public function servicios()
    {
        return $this->hasMany(TarifaServicio::class, 'tarifa_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', RecordStatus::ACTIVO->value);
    }
}
