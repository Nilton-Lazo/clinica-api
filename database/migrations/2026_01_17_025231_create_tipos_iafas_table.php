<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_iafas', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 3)->unique();
            $table->string('descripcion', 120);

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->index('estado');
            $table->index('descripcion');
        });

        DB::statement("ALTER TABLE tipos_iafas ADD CONSTRAINT tipos_iafas_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tipos_iafas DROP CONSTRAINT IF EXISTS tipos_iafas_estado_check');
        Schema::dropIfExists('tipos_iafas');
    }
};
