<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\seguridad\Policies\UserPolicy;
use App\Modules\admision\models\Especialidad;
use App\Modules\admision\policies\ficheros\EspecialidadPolicy;
use App\Modules\admision\models\Consultorio;
use App\Modules\admision\policies\ficheros\ConsultorioPolicy;
use App\Modules\admision\models\Medico;
use App\Modules\admision\policies\ficheros\MedicoPolicy;
use App\Modules\admision\models\Turno;
use App\Modules\admision\policies\ficheros\TurnoPolicy;
use App\Modules\admision\models\ProgramacionMedica;
use App\Modules\admision\policies\citas\ProgramacionMedicaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Especialidad::class => EspecialidadPolicy::class,
        Consultorio::class => ConsultorioPolicy::class,
        Medico::class => MedicoPolicy::class,
        Turno::class => TurnoPolicy::class,
        ProgramacionMedica::class => ProgramacionMedicaPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
