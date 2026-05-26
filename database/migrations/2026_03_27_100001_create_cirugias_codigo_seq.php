<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS cirugias_codigo_seq START 1 INCREMENT 1 MINVALUE 1');

        DB::statement("
            SELECT setval(
                'cirugias_codigo_seq',
                GREATEST(
                    COALESCE(
                        (SELECT MAX(codigo::int) FROM cirugias WHERE codigo ~ '^[0-9]+$'),
                        1
                    ),
                    1
                ),
                (SELECT COUNT(*) > 0 FROM cirugias WHERE codigo ~ '^[0-9]+$')
            )
        ");
    }

    public function down(): void
    {
        DB::statement('DROP SEQUENCE IF EXISTS cirugias_codigo_seq');
    }
};
