<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iafas', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 12)->unique();

            $table->unsignedBigInteger('tipo_iafa_id');

            $table->string('razon_social', 255);
            $table->string('descripcion_corta', 120);
            $table->string('ruc', 11)->unique();

            $table->string('direccion', 255)->nullable();
            $table->string('representante_legal', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('pagina_web', 200)->nullable();

            $table->date('fecha_inicio_cobertura');
            $table->date('fecha_fin_cobertura');

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->foreign('tipo_iafa_id')->references('id')->on('tipos_iafas')->restrictOnDelete();

            $table->index('estado');
            $table->index('tipo_iafa_id');
            $table->index('razon_social');
            $table->index('descripcion_corta');
        });

        DB::statement("ALTER TABLE iafas ADD CONSTRAINT iafas_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
        DB::statement("ALTER TABLE iafas ADD CONSTRAINT iafas_cobertura_check CHECK (fecha_fin_cobertura >= fecha_inicio_cobertura)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE iafas DROP CONSTRAINT IF EXISTS iafas_cobertura_check');
        DB::statement('ALTER TABLE iafas DROP CONSTRAINT IF EXISTS iafas_estado_check');
        Schema::dropIfExists('iafas');
    }
};
