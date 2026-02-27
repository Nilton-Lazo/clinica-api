<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->index(['programacion_medica_id', 'hora'], 'agenda_citas_prog_hora_index');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropIndex('agenda_citas_prog_hora_index');
        });
    }
};
