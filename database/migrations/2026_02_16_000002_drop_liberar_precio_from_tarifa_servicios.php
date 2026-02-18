<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tarifa_servicios', 'liberar_precio')) {
            Schema::table('tarifa_servicios', function (Blueprint $table) {
                $table->dropColumn('liberar_precio');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tarifa_servicios', function (Blueprint $table) {
            $table->boolean('liberar_precio')->default(false)->after('grupo_abrev');
        });
    }
};
