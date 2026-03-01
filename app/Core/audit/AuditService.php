<?php

namespace App\Core\audit;

use App\Core\audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

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
        ?int $statusCode = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $entityDisplayName = null,
        ?string $description = null
    ): void {
        $actor = $this->context->actor();

        AuditLog::create(array_filter([
            'actor_id' => $actor['id'] ?? null,
            'actor_type' => $actor['type'] ?? 'system',
            'actor_username' => $actor['username'] ?? null,
            'actor_nivel' => $actor['nivel'] ?? null,

            'action' => $action,
            'action_label' => $actionLabel,
            'description' => $description,

            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_display_name' => $entityDisplayName,
            'old_values' => $oldValues,
            'new_values' => $newValues,

            'module' => $this->context->module(),
            'route' => $this->context->route(),
            'http_method' => $this->context->method(),
            'request_id' => $this->context->requestId(),

            'ip_address' => $this->context->ip(),
            'user_agent' => $this->context->userAgent(),

            'result' => $result,
            'status_code' => $statusCode,

            'metadata' => $metadata,
        ], static fn ($v) => $v !== null));
    }

    public function logModelEvent(
        string $event,
        Model $model,
        array $oldValues = [],
        array $newValues = [],
        ?string $entityDisplayName = null
    ): void {
        $entityType = $model->getMorphClass();
        $entityId = (string) $model->getKey();
        $entityName = $this->entityShortName($entityType);

        $action = $this->modelEventToAction($event, $entityName);
        $actionLabel = $this->modelEventToActionLabel($event, $entityName);
        $description = $this->buildDescription($event, $entityName, $entityDisplayName ?? $entityId);

        $this->log(
            action: $action,
            actionLabel: $actionLabel,
            entityType: $entityType,
            entityId: $entityId,
            metadata: [],
            result: 'success',
            statusCode: null,
            oldValues: $oldValues ?: null,
            newValues: $newValues ?: null,
            entityDisplayName: $entityDisplayName,
            description: $description
        );
    }

    private function entityShortName(string $morphClass): string
    {
        $basename = class_basename($morphClass);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $basename));
    }

    private function modelEventToAction(string $event, string $entityName): string
    {
        return $entityName . '.' . $event;
    }

    private function modelEventToActionLabel(string $event, string $entityName): string
    {
        $labels = [
            'created' => 'Creó',
            'updated' => 'Modificó',
            'deleted' => 'Eliminó',
            'restored' => 'Restauró',
        ];
        $verb = $labels[$event] ?? $event;
        return $verb . ' ' . $entityName;
    }

    private function buildDescription(string $event, string $entityName, string $displayRef): string
    {
        $labels = [
            'created' => 'Creó',
            'updated' => 'Modificó',
            'deleted' => 'Eliminó',
            'restored' => 'Restauró',
        ];
        $verb = $labels[$event] ?? $event;
        return sprintf('%s %s %s', $verb, $entityName, $displayRef);
    }
}
