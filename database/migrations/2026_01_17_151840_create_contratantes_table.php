<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratantes', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 12)->unique();

            $table->string('razon_social', 255);

            $table->string('ruc', 11)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 255)->nullable();

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->index('estado');
            $table->index('razon_social');
            $table->index('ruc');
        });

        DB::statement("ALTER TABLE contratantes ADD CONSTRAINT contratantes_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
        DB::statement("ALTER TABLE contratantes ADD CONSTRAINT contratantes_ruc_check CHECK (ruc IS NULL OR ruc ~ '^[0-9]{11}$')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE contratantes DROP CONSTRAINT IF EXISTS contratantes_ruc_check');
        DB::statement('ALTER TABLE contratantes DROP CONSTRAINT IF EXISTS contratantes_estado_check');
        Schema::dropIfExists('contratantes');
    }
};
