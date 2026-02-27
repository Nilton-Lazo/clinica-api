<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->index(['estado', 'created_at'], 'pacientes_estado_created_at_index');
        });

        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->index(['programacion_medica_id', 'estado'], 'agenda_citas_prog_estado_index');
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropIndex('pacientes_estado_created_at_index');
        });

        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropIndex('agenda_citas_prog_estado_index');
        });
    }
};
