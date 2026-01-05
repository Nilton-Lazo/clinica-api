<?php

namespace App\Core\audit\Middleware;

use App\Core\audit\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditMiddleware
{
    protected AuditService $audit;

    public function __construct(AuditService $audit)
    {
        $this->audit = $audit;
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);

            if ($this->shouldAudit($request)) {
                $this->audit->log(
                    action: 'http.request',
                    actionLabel: 'Solicitud HTTP',
                    metadata: [
                        'query' => $request->query(),
                        'payload' => $request->except(['password']),
                    ],
                    result: $response->getStatusCode() < 400 ? 'success' : 'failed',
                    statusCode: $response->getStatusCode()
                );
            }

            return $response;
        } catch (Throwable $e) {
            if ($this->shouldAudit($request)) {
                $this->audit->log(
                    action: 'http.exception',
                    actionLabel: 'Excepción HTTP',
                    metadata: [
                        'exception' => class_basename($e),
                        'message' => $e->getMessage(),
                    ],
                    result: 'failed',
                    statusCode: 500
                );
            }

            throw $e;
        }
    }

    protected function shouldAudit(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}
