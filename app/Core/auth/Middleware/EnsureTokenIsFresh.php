<?php

namespace App\Core\auth\Middleware;

use App\Core\audit\Facades\Audit;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (!$user || !$token) {
            return $next($request);
        }

        $now = Carbon::now();

        $idleMinutes = max(1, (int) config('session_limits.idle_minutes', 15));
        $maxHours = max(1 / 60, (float) config('session_limits.max_hours', 8));
        $maxSeconds = (int) ceil($maxHours * 3600);

        $tokenKey = 'session:last_activity:' . $token->id;

        // Si se pierde cache (reinicio/evicción), usamos fallback del token.
        $lastActivityRaw = Cache::get($tokenKey);
        if (!$lastActivityRaw) {
            $fallback = $token->last_used_at ?? $token->created_at;
            if ($fallback) {
                $lastActivityRaw = Carbon::parse($fallback)->toDateTimeString();
                Cache::put($tokenKey, $lastActivityRaw, now()->addSeconds($maxSeconds));
            }
        }

        if ($lastActivityRaw) {
            $lastActivity = Carbon::parse($lastActivityRaw);
            if ($lastActivity->diffInSeconds($now) >= ($idleMinutes * 60)) {
                $token->delete();
                Cache::forget($tokenKey);

                Audit::log(
                    action: 'session.expired.idle',
                    actionLabel: 'Sesión expirada por inactividad',
                    entityType: get_class($user),
                    entityId: (string) $user->id,
                    result: 'failed',
                    statusCode: 401
                );

                return response()->json([
                    'message' => 'Sesión expirada por inactividad.',
                    'code' => 'SESSION_EXPIRED_IDLE',
                ], 401);
            }
        }

        $createdAt = Carbon::parse($token->created_at);

        if ($createdAt->diffInSeconds($now) >= $maxSeconds) {
            $token->delete();
            Cache::forget($tokenKey);

            Audit::log(
                action: 'session.expired.absolute',
                actionLabel: 'Sesión expirada por tiempo máximo',
                entityType: get_class($user),
                entityId: (string) $user->id,
                result: 'failed',
                statusCode: 401
            );

            return response()->json([
                'message' => 'Sesión expirada por tiempo máximo.',
                'code' => 'SESSION_EXPIRED_ABSOLUTE',
            ], 401);
        }

        Cache::put($tokenKey, $now->toDateTimeString(), now()->addSeconds($maxSeconds));

        return $next($request);
    }
}
