<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultorios', function (Blueprint $table) {
            $table->id();
            $table->string('abreviatura', 10)->unique();
            $table->string('descripcion', 255);
            $table->boolean('es_tercero')->default(false);
            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->index('estado');
            $table->index('descripcion');
            $table->index('es_tercero');
        });

        DB::statement("ALTER TABLE consultorios ADD CONSTRAINT consultorios_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE consultorios DROP CONSTRAINT IF EXISTS consultorios_estado_check');
        Schema::dropIfExists('consultorios');
    }
};
