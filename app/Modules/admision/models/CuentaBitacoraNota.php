<?php

namespace App\Modules\admision\models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaBitacoraNota extends Model
{
    protected $table = 'cuenta_bitacora_notas';

    protected $fillable = [
        'cuenta_id',
        'paciente_id',
        'user_id',
        'contenido',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
