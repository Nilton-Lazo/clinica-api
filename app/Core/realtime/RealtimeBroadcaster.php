<?php

namespace App\Core\realtime;

use App\Events\SystemEntityChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealtimeBroadcaster
{
    public function entityChanged(
        string $module,
        string $entity,
        string $action,
        int|string|null $id = null,
        ?string $scope = null,
        array $metadata = [],
        ?int $actorId = null,
    ): void {
        $event = new SystemEntityChanged(
            module: $module,
            entity: $entity,
            action: $action,
            id: $id,
            scope: $scope,
            actorId: $actorId ?? auth()->id(),
            metadata: $metadata,
        );

        $emit = fn () => $this->broadcast($event);

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($emit);
            return;
        }

        $emit();
    }

    private function broadcast(SystemEntityChanged $event): void
    {
        try {
            broadcast($event);
        } catch (\Throwable $e) {
            Log::warning('No se pudo emitir evento realtime.', [
                'module' => $event->module,
                'entity' => $event->entity,
                'action' => $event->action,
                'id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
