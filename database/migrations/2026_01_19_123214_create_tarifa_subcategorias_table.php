<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifa_subcategorias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tarifa_id')
                ->constrained('tarifas')
                ->restrictOnDelete();

            $table->foreignId('categoria_id')
                ->constrained('tarifa_categorias')
                ->restrictOnDelete();

            $table->char('codigo', 2);
            $table->string('nombre', 255);

            $table->string('estado', 12)->default('ACTIVO');

            $table->timestamps();

            $table->unique(['categoria_id', 'codigo']);
            $table->index(['tarifa_id', 'categoria_id', 'estado']);
        });

        DB::statement("ALTER TABLE tarifa_subcategorias ADD CONSTRAINT chk_tarifa_subcategorias_codigo_format CHECK (codigo ~ '^[0-9]{2}$')");
        DB::statement("ALTER TABLE tarifa_subcategorias ADD CONSTRAINT chk_tarifa_subcategorias_estado CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifa_subcategorias');
    }
};
