<?php

namespace App\Modules\login\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Core\audit\Facades\Audit;

class LoginService
{
    public function login(string $identifier, string $password): array
    {
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $identifier, 'password' => $password])) {
            Audit::log(
                action: 'auth.login.failed',
                actionLabel: 'Login fallido',
                metadata: [
                    'identifier' => $identifier,
                ],
                result: 'failed',
                statusCode: 401
            );

            throw ValidationException::withMessages([
                'identifier' => ['Credenciales incorrectas.'],
            ]);
        }

        $user = Auth::user();

        if ($user->estado !== 'activo') {
            Auth::logout();

            Audit::log(
                action: 'auth.login.blocked',
                actionLabel: 'Login bloqueado por estado',
                entityType: User::class,
                entityId: (string) $user->id,
                metadata: [
                    'estado' => $user->estado,
                ],
                result: 'failed',
                statusCode: 403
            );

            throw ValidationException::withMessages([
                'identifier' => ['Usuario inactivo.'],
            ]);
        }

        $token = $user->createToken('erp-api')->plainTextToken;

        Audit::log(
            action: 'auth.login.success',
            actionLabel: 'Login exitoso',
            entityType: User::class,
            entityId: (string) $user->id,
            metadata: [
                'nivel' => $user->nivel,
            ],
            result: 'success',
            statusCode: 200
        );

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'nivel' => $user->nivel,
                'estado' => $user->estado,
            ],
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();

        Audit::log(
            action: 'auth.logout',
            actionLabel: 'Cierre de sesión',
            entityType: User::class,
            entityId: (string) $user->id,
            result: 'success',
            statusCode: 200
        );
    }
}
