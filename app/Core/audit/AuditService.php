<?php

namespace App\Core\audit;

use App\Core\audit\Models\AuditLog;

class AuditService
{
    protected AuditContext $context;

    public function __construct(AuditContext $context)
    {
        $this->context = $context;
    }

    public function log(
        string $action,
        ?string $actionLabel = null,
        ?string $entityType = null,
        ?string $entityId = null,
        array $metadata = [],
        string $result = 'success',
        ?int $statusCode = null
    ): void {
        $actor = $this->context->actor();

        AuditLog::create([
            'actor_id' => $actor['id'] ?? null,
            'actor_type' => $actor['type'] ?? 'system',
            'actor_username' => $actor['username'] ?? null,
            'actor_nivel' => $actor['nivel'] ?? null,

            'action' => $action,
            'action_label' => $actionLabel,

            'entity_type' => $entityType,
            'entity_id' => $entityId,

            'module' => $this->context->module(),
            'route' => $this->context->route(),
            'http_method' => $this->context->method(),
            'request_id' => $this->context->requestId(),

            'ip_address' => $this->context->ip(),
            'user_agent' => $this->context->userAgent(),

            'result' => $result,
            'status_code' => $statusCode,

            'metadata' => $metadata,
        ]);
    }
}
