<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('programaciones_medicas', function (Blueprint $table) {
            $table->index(['estado', 'fecha'], 'programaciones_medicas_estado_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::table('programaciones_medicas', function (Blueprint $table) {
            $table->dropIndex('programaciones_medicas_estado_fecha_index');
        });
    }
};
