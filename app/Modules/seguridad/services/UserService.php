<?php

namespace App\Modules\seguridad\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Core\audit\Facades\Audit;

class UserService
{
    public function create(array $data): User
    {
        $baseUsername = $this->generateBaseUsername(
            $data['nombres'],
            $data['apellido_paterno']
        );

        $username = $this->generateUniqueUsername($baseUsername);

        $user = User::create([
            'name' => $this->buildFullName(
                $data['nombres'],
                $data['apellido_paterno'],
                $data['apellido_materno'] ?? null
            ),
            'username' => $username,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'nivel' => $data['nivel'],
            'estado' => $data['estado'],
            'nombres' => $data['nombres'],
            'apellido_paterno' => $data['apellido_paterno'],
            'apellido_materno' => $data['apellido_materno'] ?? null,
        ]);

        Audit::log(
            action: 'user.create',
            actionLabel: 'Creación de usuario',
            entityType: User::class,
            entityId: (string) $user->id,
            metadata: [
                'email' => $user->email,
                'nivel' => $user->nivel,
                'estado' => $user->estado,
            ],
            result: 'success',
            statusCode: 201
        );

        return $user;
    }

    private function generateBaseUsername(string $nombres, string $apellidoPaterno): string
    {
        return Str::lower(
            Str::substr($nombres, 0, 1) . $apellidoPaterno
        );
    }

    private function generateUniqueUsername(string $base): string
    {
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    private function buildFullName(
        string $nombres,
        string $apellidoPaterno,
        ?string $apellidoMaterno
    ): string {
        return trim(
            $nombres . ' ' .
            $apellidoPaterno . ' ' .
            ($apellidoMaterno ?? '')
        );
    }
}
