<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifa_servicios', function (Blueprint $table) {
            $table->boolean('desea_liberar_precio')->default(false)->after('grupo_abrev');
        });
    }

    public function down(): void
    {
        Schema::table('tarifa_servicios', function (Blueprint $table) {
            $table->dropColumn('desea_liberar_precio');
        });
    }
};
