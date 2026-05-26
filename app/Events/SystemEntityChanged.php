<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemEntityChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $module,
        public readonly string $entity,
        public readonly string $action,
        public readonly int|string|null $id = null,
        public readonly ?string $scope = null,
        public readonly ?int $actorId = null,
        public readonly array $metadata = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('system'),
            new PrivateChannel('module.'.$this->module),
        ];
    }

    public function broadcastAs(): string
    {
        return 'system.entity.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'module' => $this->module,
            'entity' => $this->entity,
            'action' => $this->action,
            'id' => $this->id,
            'scope' => $this->scope,
            'actor_id' => $this->actorId,
            'metadata' => $this->metadata ?: null,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
