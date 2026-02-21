<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->boolean('deshabilita_soat')->default(false)->after('descripcion_tarifa');
        });

        DB::statement("UPDATE tarifas SET deshabilita_soat = true WHERE LOWER(TRIM(COALESCE(descripcion_tarifa, ''))) IN ('particular', 'privado')");
    }

    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn('deshabilita_soat');
        });
    }
};
