<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifa_servicios', function (Blueprint $table) {
            $table->string('grupo_codigo', 20)->nullable()->after('unidad');
            $table->string('grupo_descripcion', 255)->nullable()->after('grupo_codigo');
            $table->string('grupo_abrev', 20)->nullable()->after('grupo_descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('tarifa_servicios', function (Blueprint $table) {
            $table->dropColumn(['grupo_codigo', 'grupo_descripcion', 'grupo_abrev']);
        });
    }
};
