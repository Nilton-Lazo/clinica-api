<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemKeySeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('tipos_clientes')) {
            return;
        }

        if (!Schema::hasColumn('tipos_clientes', 'system_key')) {
            return;
        }

        $hasParticular = DB::table('tipos_clientes')->where('system_key', 'DEFAULT_PARTICULAR')->exists();
        $hasPrivado = DB::table('tipos_clientes')->where('system_key', 'DEFAULT_PRIVADO')->exists();

        if ($hasParticular && $hasPrivado) {
            return;
        }

        $needed = [];
        if (!$hasParticular) $needed[] = 'DEFAULT_PARTICULAR';
        if (!$hasPrivado) $needed[] = 'DEFAULT_PRIVADO';

        $candidates = DB::table('tipos_clientes')
            ->whereNull('system_key')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $assignedIds = [];

        foreach ($needed as $systemKey) {
            $id = null;
            foreach ($candidates as $cid) {
                if (!in_array($cid, $assignedIds, true)) {
                    $id = $cid;
                    break;
                }
            }

            if ($id !== null) {
                DB::table('tipos_clientes')->where('id', $id)->update([
                    'system_key' => $systemKey,
                    'updated_at' => now(),
                ]);
                $assignedIds[] = $id;
            }
        }

        $hasParticular = DB::table('tipos_clientes')->where('system_key', 'DEFAULT_PARTICULAR')->exists();
        $hasPrivado = DB::table('tipos_clientes')->where('system_key', 'DEFAULT_PRIVADO')->exists();

        if ($hasParticular && $hasPrivado) {
            return;
        }

        $tarifas = DB::table('tarifas')->orderBy('id')->pluck('id')->all();
        $iafas = DB::table('iafas')->orderBy('id')->pluck('id')->all();
        $contratantes = DB::table('contratantes')->orderBy('id')->pluck('id')->all();

        if (count($tarifas) === 0 || count($iafas) === 0 || count($contratantes) === 0) {
            throw new \RuntimeException('Faltan datos base: tarifas/iafas/contratantes. Seed primero esos catálogos.');
        }

        $usedPairs = DB::table('tipos_clientes')
            ->select('contratante_id', 'tarifa_id')
            ->get()
            ->map(fn ($r) => $r->contratante_id . ':' . $r->tarifa_id)
            ->all();

        $pickPair = function (array $used) use ($contratantes, $tarifas): array {
            foreach ($contratantes as $c) {
                foreach ($tarifas as $t) {
                    $k = $c . ':' . $t;
                    if (!in_array($k, $used, true)) {
                        return [$c, $t];
                    }
                }
            }
            return [0, 0];
        };

        $insertDefault = function (string $systemKey, string $codigo, string $desc, array &$usedPairs) use ($iafas, $pickPair): void {
            [$contrId, $tarifaId] = $pickPair($usedPairs);

            if ($contrId === 0 || $tarifaId === 0) {
                throw new \RuntimeException("No hay combinaciones suficientes para crear $systemKey sin chocar con unique(contratante_id, tarifa_id). Crea otra tarifa o contratante.");
            }

            $usedPairs[] = $contrId . ':' . $tarifaId;

            DB::table('tipos_clientes')->updateOrInsert(
                ['system_key' => $systemKey],
                [
                    'codigo' => $codigo,
                    'tarifa_id' => $tarifaId,
                    'iafa_id' => $iafas[0],
                    'contratante_id' => $contrId,
                    'descripcion_tipo_cliente' => $desc,
                    'estado' => 'ACTIVO',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        };

        if (!DB::table('tipos_clientes')->where('system_key', 'DEFAULT_PARTICULAR')->exists()) {
            $insertDefault('DEFAULT_PARTICULAR', 'PARTICULAR', 'PARTICULAR/PARTICULAR', $usedPairs);
        }

        if (!DB::table('tipos_clientes')->where('system_key', 'DEFAULT_PRIVADO')->exists()) {
            $insertDefault('DEFAULT_PRIVADO', 'PRIVADO', 'PRIVADO/PRIVADO', $usedPairs);
        }
    }
}
