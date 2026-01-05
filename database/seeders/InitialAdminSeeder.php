<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@clinica.local')->exists()) {
            return;
        }

        User::create([
            'name' => 'Administrador General',
            'username' => 'admin',
            'nombres' => 'Administrador',
            'apellido_paterno' => 'General',
            'apellido_materno' => null,
            'email' => 'admin@clinica.local',
            'password' => Hash::make('admin123'),
            'nivel' => 'administrador',
            'estado' => 'activo',
        ]);
    }
}
