<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE clientes DROP CONSTRAINT IF EXISTS clientes_dni_o_ruc_check');

        DB::table('clientes')->whereNull('dni_o_ruc')->update(['dni_o_ruc' => '00000000']);

        DB::statement('ALTER TABLE clientes ALTER COLUMN dni_o_ruc SET NOT NULL');

        DB::statement("ALTER TABLE clientes ADD CONSTRAINT clientes_dni_o_ruc_check CHECK (dni_o_ruc ~ '^[0-9]{8}$' OR dni_o_ruc ~ '^[0-9]{11}$')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE clientes DROP CONSTRAINT IF EXISTS clientes_dni_o_ruc_check');

        DB::statement('ALTER TABLE clientes ALTER COLUMN dni_o_ruc DROP NOT NULL');

        DB::statement("ALTER TABLE clientes ADD CONSTRAINT clientes_dni_o_ruc_check CHECK (dni_o_ruc IS NULL OR dni_o_ruc ~ '^[0-9]{8}$' OR dni_o_ruc ~ '^[0-9]{11}$')");
    }
};
