<?php

namespace App\Core\auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateBearerFromQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            $token = $request->query('access_token');
            if (is_string($token) && trim($token) !== '') {
                $request->headers->set('Authorization', 'Bearer '.trim($token));
            }
        }

        return $next($request);
    }
}
