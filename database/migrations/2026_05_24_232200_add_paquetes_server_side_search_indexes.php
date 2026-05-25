<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_paquetes_tarifa_estado_codigo ON paquetes (tarifa_id, estado, codigo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_paquetes_tarifa_estado_descripcion ON paquetes (tarifa_id, estado, descripcion)');
        DB::statement("CREATE INDEX IF NOT EXISTS idx_paquetes_codigo_trgm ON paquetes USING gin (codigo gin_trgm_ops) WHERE estado = 'ACTIVO'");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_paquetes_descripcion_trgm ON paquetes USING gin (descripcion gin_trgm_ops) WHERE estado = 'ACTIVO'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_paquetes_descripcion_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_paquetes_codigo_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_paquetes_tarifa_estado_descripcion');
        DB::statement('DROP INDEX IF EXISTS idx_paquetes_tarifa_estado_codigo');
    }
};
