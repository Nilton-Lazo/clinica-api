<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('descripcion', 255);
            $table->string('abrev', 20)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos_servicio');
    }
};
