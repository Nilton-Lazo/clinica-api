<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_formas_pago', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);
            $table->string('estado', 20)->default('ACTIVO');
            $table->timestamps();

            $table->index(['estado', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_formas_pago');
    }
};
