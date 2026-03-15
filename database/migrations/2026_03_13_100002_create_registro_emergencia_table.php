<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_emergencia', function (Blueprint $table) {
            $table->id();
            $table->string('orden', 50)->nullable()->index();
            $table->string('hora', 20)->nullable();
            $table->string('numero_hc', 50)->nullable()->index();
            $table->string('apellidos_nombres', 255)->nullable()->index();
            $table->string('sexo', 20)->nullable();
            $table->string('tipo_cliente', 100)->nullable();
            $table->date('fecha')->nullable()->index();
            $table->string('cuenta', 100)->nullable();
            $table->string('medico_emergencia', 255)->nullable();
            $table->string('medico_especialista', 255)->nullable();
            $table->string('topico', 100)->nullable()->index();
            $table->string('numero_cuenta', 100)->nullable();
            $table->string('estado', 20)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_emergencia');
    }
};
