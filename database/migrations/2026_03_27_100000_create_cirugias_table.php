<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cirugias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('descripcion', 255);
            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->index('estado');
            $table->index('descripcion');
        });

        DB::statement("ALTER TABLE cirugias ADD CONSTRAINT cirugias_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cirugias DROP CONSTRAINT IF EXISTS cirugias_estado_check');
        Schema::dropIfExists('cirugias');
    }
};
