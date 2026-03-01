<?php

namespace App\Console\Commands;

use App\Core\notifications\Models\UserNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneUserNotifications extends Command
{
    protected $signature = 'notifications:prune
        {--days=180 : Eliminar notificaciones con antiguedad mayor a N dias}
        {--max-per-user=2000 : Mantener maximo de notificaciones por usuario}
        {--purge-technical=1 : Eliminar notificaciones tecnicas (navigation/telemetria)}
        {--dry-run : Solo mostrar conteos, sin eliminar}';

    protected $description = 'Aplica retencion automatica sobre user_notifications';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $maxPerUser = max(0, (int) $this->option('max-per-user'));
        $purgeTechnical = (int) $this->option('purge-technical') === 1;
        $dryRun = (bool) $this->option('dry-run');

        $deletedTechnical = 0;
        $deletedByAge = 0;
        $deletedByCap = 0;

        if ($purgeTechnical) {
            $technicalQuery = UserNotification::query()
                ->whereIn('entity_type', ['navigation', 'telemetria_navegacion']);
            $deletedTechnical = $dryRun ? (clone $technicalQuery)->count() : $technicalQuery->delete();
        }

        if ($days > 0) {
            $threshold = CarbonImmutable::now()->subDays($days);
            $ageQuery = UserNotification::query()->where('created_at', '<', $threshold);
            $deletedByAge = $dryRun ? (clone $ageQuery)->count() : $ageQuery->delete();
        }

        if ($maxPerUser > 0) {
            $sqlCount = <<<SQL
SELECT COUNT(*) AS total
FROM (
    SELECT id, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC, id DESC) AS rn
    FROM user_notifications
) ranked
WHERE ranked.rn > ?
SQL;
            $rowsToDelete = (int) (DB::selectOne($sqlCount, [$maxPerUser])->total ?? 0);
            $deletedByCap = $rowsToDelete;

            if (!$dryRun && $rowsToDelete > 0) {
                $sqlDelete = <<<SQL
DELETE FROM user_notifications n
USING (
    SELECT id
    FROM (
        SELECT id, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC, id DESC) AS rn
        FROM user_notifications
    ) ranked
    WHERE ranked.rn > ?
) stale
WHERE n.id = stale.id
SQL;
                DB::delete($sqlDelete, [$maxPerUser]);
            }
        }

        $totalDeleted = $deletedTechnical + $deletedByAge + $deletedByCap;
        $mode = $dryRun ? 'DRY-RUN' : 'APLICADO';

        $this->line("Modo: {$mode}");
        $this->line("Eliminadas tecnicas: {$deletedTechnical}");
        $this->line("Eliminadas por antiguedad: {$deletedByAge}");
        $this->line("Eliminadas por limite por usuario: {$deletedByCap}");
        $this->info("Total afectadas: {$totalDeleted}");

        return self::SUCCESS;
    }
}
