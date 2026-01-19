<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS ux_tarifas_single_base
            ON tarifas (tarifa_base)
            WHERE tarifa_base IS TRUE
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'chk_tarifas_base_activo'
                ) THEN
                    ALTER TABLE tarifas
                    ADD CONSTRAINT chk_tarifas_base_activo
                    CHECK (NOT tarifa_base OR estado = 'ACTIVO');
                END IF;
            END $$;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS ux_tarifas_single_base");

        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'chk_tarifas_base_activo'
                ) THEN
                    ALTER TABLE tarifas
                    DROP CONSTRAINT chk_tarifas_base_activo;
                END IF;
            END $$;
        ");
    }
};
