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
            $table->boolean('es_precio_directo')->default(false)->after('descripcion_tarifa');
        });

        DB::statement('UPDATE tarifas SET es_precio_directo = deshabilita_soat WHERE 1=1');

        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn('deshabilita_soat');
        });
    }

    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->boolean('deshabilita_soat')->default(false)->after('descripcion_tarifa');
        });

        DB::statement('UPDATE tarifas SET deshabilita_soat = es_precio_directo WHERE 1=1');

        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn('es_precio_directo');
        });
    }
};
