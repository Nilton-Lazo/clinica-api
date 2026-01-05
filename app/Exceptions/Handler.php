<?php

namespace App\Exceptions;

use App\Core\audit\Facades\Audit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (AuthorizationException $e, $request) {
            Audit::log(
                action: 'auth.denied',
                actionLabel: 'Acceso denegado',
                metadata: [
                    'exception' => class_basename($e),
                ],
                result: 'failed',
                statusCode: 403
            );

            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        });

        $this->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Error HTTP.',
                ], $e->getStatusCode());
            }

            Audit::log(
                action: 'system.exception',
                actionLabel: 'Excepción no controlada',
                metadata: [
                    'exception' => class_basename($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                result: 'failed',
                statusCode: 500
            );

            return response()->json([
                'message' => 'Error interno del servidor.',
            ], 500);
        });
    }
}
