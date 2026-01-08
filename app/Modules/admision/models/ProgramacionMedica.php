<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramacionMedica extends Model
{
    protected $table = 'programaciones_medicas';

    protected $fillable = [
        'fecha',
        'especialidad_id',
        'medico_id',
        'consultorio_id',
        'turno_id',
        'cupos',
        'tipo',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cupos' => 'integer',
    ];

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function consultorio(): BelongsTo
    {
        return $this->belongsTo(Consultorio::class, 'consultorio_id');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }
}
