<?php

namespace App\Modules\admision\policies\pacientes;

use App\Modules\admision\models\Paciente;

class PacientePolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function view($user, Paciente $paciente): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Paciente $paciente): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Paciente $paciente): bool
    {
        return $user !== null;
    }
}
