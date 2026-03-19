<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ficheros_emergencia_servicios_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tarifa_id');
            $table->string('codigo_servicio');
            $table->timestamps();

            $table->foreign('tarifa_id')
                  ->references('id')
                  ->on('tarifas')
                  ->onDelete('cascade');

            $table->unique(['tarifa_id', 'codigo_servicio'], 'idx_tarifa_servicio_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ficheros_emergencia_servicios_defaults');
    }
};
