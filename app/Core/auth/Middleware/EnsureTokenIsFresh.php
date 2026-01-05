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

        $idleMinutes = (int) env('SESSION_IDLE_MINUTES', 15);
        $maxHours = (int) env('SESSION_MAX_HOURS', 8);

        $tokenKey = 'session:last_activity:' . $token->id;

        $lastActivity = Cache::get($tokenKey);

        if ($lastActivity) {
            $lastActivity = Carbon::parse($lastActivity);

            if ($lastActivity->diffInMinutes($now) >= $idleMinutes) {
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

        if ($createdAt->diffInHours($now) >= $maxHours) {
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

        Cache::put($tokenKey, $now->toDateTimeString(), now()->addHours($maxHours));

        return $next($request);
    }
}
