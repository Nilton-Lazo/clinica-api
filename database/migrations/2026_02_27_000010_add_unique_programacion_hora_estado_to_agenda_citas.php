<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropUnique('agenda_citas_unique_slot');

            $table->unique(
                ['programacion_medica_id', 'hora', 'estado'],
                'agenda_citas_prog_hora_estado_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropUnique('agenda_citas_prog_hora_estado_unique');

            $table->unique(
                ['programacion_medica_id', 'hora'],
                'agenda_citas_unique_slot'
            );
        });
    }
};

