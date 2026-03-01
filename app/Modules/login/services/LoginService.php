<?php

namespace App\Modules\login\Services;

use App\Core\audit\Facades\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginService
{
    private const LOGIN_FIELDS = [
        'id', 'username', 'name', 'nombres', 'apellido_paterno', 'apellido_materno',
        'nivel', 'estado', 'password',
    ];

    public function login(string $identifier, string $password, ?string $deviceName = null): array
    {
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $select = array_unique(array_merge(self::LOGIN_FIELDS, [$field]));
        $user = User::where($field, $identifier)->select($select)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->auditLoginFailed($identifier);
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
                metadata: ['estado' => $user->estado],
                result: 'failed',
                statusCode: 403
            );
            throw ValidationException::withMessages([
                'identifier' => ['Usuario inactivo.'],
            ]);
        }

        $maxHours = max(1 / 60, (float) config('session_limits.max_hours', 8));
        $maxSeconds = (int) ceil($maxHours * 3600);
        $tokenName = $deviceName ? ('erp-api:' . $this->normalizeTokenName($deviceName)) : 'erp-api';
        $newToken = $user->createToken($tokenName, ['*'], now()->addSeconds($maxSeconds));

        Cache::put(
            'session:last_activity:' . $newToken->accessToken->id,
            now()->toDateTimeString(),
            now()->addSeconds($maxSeconds)
        );

        Audit::log(
            action: 'auth.login.success',
            actionLabel: 'Login exitoso',
            entityType: User::class,
            entityId: (string) $user->id,
            metadata: ['nivel' => $user->nivel, 'token_name' => $tokenName],
            result: 'success',
            statusCode: 200
        );

        $user->makeHidden(['password']);
        return [
            'user' => $user->toArray(),
            'token' => $newToken->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token) {
            Cache::forget('session:last_activity:' . $token->id);
            $token->delete();
        }

        Audit::log(
            action: 'auth.logout',
            actionLabel: 'Cierre de sesión',
            entityType: User::class,
            entityId: (string) $user->id,
            result: 'success',
            statusCode: 200
        );
    }

    private function auditLoginFailed(string $identifier): void
    {
        Audit::log(
            action: 'auth.login.failed',
            actionLabel: 'Login fallido',
            metadata: ['identifier' => $identifier],
            result: 'failed',
            statusCode: 401
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
