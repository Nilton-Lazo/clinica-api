<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TarifaPrecioDirectoSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasColumn('tarifas', 'es_precio_directo')) {
            return;
        }

        $updated = DB::table('tarifas')
            ->whereRaw("LOWER(TRIM(COALESCE(descripcion_tarifa, ''))) IN ('particular', 'privado')")
            ->update(['es_precio_directo' => true]);

        if ($updated > 0) {
            $this->command->info("TarifaPrecioDirectoSeeder: {$updated} tarifa(s) marcadas como precio directo (Particular/Privado).");
        }
    }
}
