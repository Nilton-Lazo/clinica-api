<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paquetes', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 12)->unique();

            $table->string('descripcion', 255);

            $table->foreignId('tarifa_id')->constrained('tarifas')->cascadeOnUpdate()->restrictOnDelete();

            $table->decimal('precio_sin_igv', 14, 4);

            $table->date('vigencia_actual');

            $table->unsignedInteger('dias_hospitalizacion');

            $table->string('cuenta_contabilidad', 255)->nullable();

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->index('estado');
            $table->index('descripcion');
            $table->index('vigencia_actual');
        });

        DB::statement("ALTER TABLE paquetes ADD CONSTRAINT paquetes_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
        DB::statement('ALTER TABLE paquetes ADD CONSTRAINT paquetes_precio_sin_igv_check CHECK (precio_sin_igv >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE paquetes DROP CONSTRAINT IF EXISTS paquetes_precio_sin_igv_check');
        DB::statement('ALTER TABLE paquetes DROP CONSTRAINT IF EXISTS paquetes_estado_check');
        Schema::dropIfExists('paquetes');
    }
};
