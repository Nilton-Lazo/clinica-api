<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuenta_bitacora_notas', function (Blueprint $table) {
            $table->dropForeign(['cuenta_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE cuenta_bitacora_notas MODIFY cuenta_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE cuenta_bitacora_notas ALTER COLUMN cuenta_id DROP NOT NULL');
        } else {
            throw new \RuntimeException(
                'Migración cuenta_bitacora_notas: el driver '.$driver.' requiere ajuste manual (cuenta_id nullable). Use MySQL o PostgreSQL.'
            );
        }

        Schema::table('cuenta_bitacora_notas', function (Blueprint $table) {
            $table->foreign('cuenta_id')->references('id')->on('cuentas')->cascadeOnDelete();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->cascadeOnDelete();
            $table->index(['paciente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('cuenta_bitacora_notas', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
            $table->dropIndex(['paciente_id', 'created_at']);
            $table->dropColumn('paciente_id');
        });

        DB::table('cuenta_bitacora_notas')->whereNull('cuenta_id')->delete();

        Schema::table('cuenta_bitacora_notas', function (Blueprint $table) {
            $table->dropForeign(['cuenta_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE cuenta_bitacora_notas MODIFY cuenta_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE cuenta_bitacora_notas ALTER COLUMN cuenta_id SET NOT NULL');
        }

        Schema::table('cuenta_bitacora_notas', function (Blueprint $table) {
            $table->foreign('cuenta_id')->references('id')->on('cuentas')->cascadeOnDelete();
        });
    }
};
