<?php

namespace App\Core\security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // COOP/CORP: útiles si sesrive UI desde el mismo origen.
        // Si algún día se rompe integraciones/embeds, se pueden ajustar.
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP SOLO si la respuesta es HTML (Laravel está sirviendo una UI).
        $contentType = (string) ($response->headers->get('Content-Type') ?? '');
        $isHtml = str_contains($contentType, 'text/html');

        if ($isHtml) {
            $isLocal = app()->environment('local');

            if ($isLocal) {
                $connectSrc = [
                    "'self'",
                    "http://localhost:5173",
                    "http://127.0.0.1:5173",
                    "http://192.168.1.35:5173",
                    "ws://localhost:5173",
                    "ws://127.0.0.1:5173",
                    "ws://192.168.1.35:5173",
                ];

                $csp = implode('; ', [
                    "default-src 'self'",
                    "base-uri 'self'",
                    "object-src 'none'",
                    "frame-ancestors 'none'",
                    "img-src 'self' data: blob:",
                    "font-src 'self' data:",
                    "style-src 'self' 'unsafe-inline'",
                    "script-src 'self' 'unsafe-eval' 'unsafe-inline'",
                    "connect-src " . implode(' ', $connectSrc),
                    "form-action 'self'",
                ]);
            } else {
                // Producción: lo ideal es NO usar unsafe-inline/unsafe-eval.
                $csp = implode('; ', [
                    "default-src 'self'",
                    "base-uri 'self'",
                    "object-src 'none'",
                    "frame-ancestors 'none'",
                    "img-src 'self' data:",
                    "font-src 'self' data:",
                    "style-src 'self'",
                    "script-src 'self'",
                    "connect-src 'self'",
                    "form-action 'self'",
                    "upgrade-insecure-requests",
                ]);
            }

            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
