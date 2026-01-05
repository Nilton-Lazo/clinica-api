<?php

namespace App\Core\audit;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditContext
{
    protected Request $request;
    protected string $requestId;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->requestId = (string) Str::uuid();
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function actor(): ?array
    {
        $user = $this->request->user();

        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'username' => $user->username ?? null,
            'nivel' => $user->nivel ?? null,
            'type' => 'user',
        ];
    }

    public function module(): string
    {
        $route = $this->request->route()?->getActionName();

        if (!$route) {
            return 'unknown';
        }

        return str_contains($route, 'Modules')
            ? explode('\\', explode('Modules\\', $route)[1])[0]
            : 'core';
    }

    public function route(): string
    {
        return $this->request->path();
    }

    public function method(): string
    {
        return $this->request->method();
    }

    public function ip(): ?string
    {
        return $this->request->ip();
    }

    public function userAgent(): ?string
    {
        return $this->request->userAgent();
    }
}
