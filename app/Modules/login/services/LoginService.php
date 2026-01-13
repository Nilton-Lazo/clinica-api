<?php

namespace App\Modules\login\Services;

use App\Core\audit\Facades\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginService
{
    public function login(string $identifier, string $password, ?string $deviceName = null): array
    {
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $identifier)->first();

        if (!$user || !Hash::check($password, $user->password)) {
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

        if ($user->estado !== 'activo') {
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

        $tokenName = $deviceName ? ('erp-api:' . $this->normalizeTokenName($deviceName)) : 'erp-api';

        $newToken = $user->createToken($tokenName);

        $maxHours = (int) env('SESSION_MAX_HOURS', 8);
        Cache::put(
            'session:last_activity:' . $newToken->accessToken->id,
            now()->toDateTimeString(),
            now()->addHours($maxHours)
        );

        Audit::log(
            action: 'auth.login.success',
            actionLabel: 'Login exitoso',
            entityType: User::class,
            entityId: (string) $user->id,
            metadata: [
                'nivel' => $user->nivel,
                'token_name' => $tokenName,
            ],
            result: 'success',
            statusCode: 200
        );

        return [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'nombres' => $user->nombres,
                'apellido_paterno' => $user->apellido_paterno,
                'apellido_materno' => $user->apellido_materno,
                'nivel' => $user->nivel,
                'estado' => $user->estado,
            ],
            'token' => $newToken->plainTextToken,
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

    private function normalizeTokenName(string $value): string
    {
        $v = trim($value);
        if ($v === '') return 'device';

        $v = preg_replace('/\s+/', ' ', $v) ?? $v;

        return substr($v, 0, 60);
    }
}
