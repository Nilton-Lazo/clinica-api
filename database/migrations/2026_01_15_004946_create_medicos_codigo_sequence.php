<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE SEQUENCE IF NOT EXISTS medicos_codigo_seq START 1 INCREMENT 1 MINVALUE 1");

        DB::statement("
            SELECT setval(
                'medicos_codigo_seq',
                COALESCE((SELECT MAX(codigo::int) FROM medicos WHERE codigo ~ '^[0-9]+$'), 0),
                true
            )
        ");
    }

    public function down(): void
    {
        DB::statement("DROP SEQUENCE IF EXISTS medicos_codigo_seq");
    }
};
