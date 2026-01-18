<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 12)->unique();

            $table->boolean('requiere_acreditacion')->default(false);
            $table->boolean('tarifa_base')->default(false);

            $table->string('descripcion_tarifa', 255);

            $table->unsignedBigInteger('iafa_id')->nullable();

            $table->decimal('factor_clinica', 10, 2)->default(1.00);
            $table->decimal('factor_laboratorio', 10, 2)->default(1.00);
            $table->decimal('factor_ecografia', 10, 2)->default(1.00);
            $table->decimal('factor_procedimientos', 10, 2)->default(1.00);
            $table->decimal('factor_rayos_x', 10, 2)->default(1.00);
            $table->decimal('factor_tomografia', 10, 2)->default(1.00);
            $table->decimal('factor_patologia', 10, 2)->default(1.00);
            $table->decimal('factor_medicina_fisica', 10, 2)->default(1.00);
            $table->decimal('factor_resonancia', 10, 2)->default(1.00);
            $table->decimal('factor_honorarios_medicos', 10, 2)->default(1.00);
            $table->decimal('factor_medicinas', 10, 2)->default(1.00);
            $table->decimal('factor_equipos_oxigeno', 10, 2)->default(1.00);
            $table->decimal('factor_banco_sangre', 10, 2)->default(1.00);
            $table->decimal('factor_mamografia', 10, 2)->default(1.00);
            $table->decimal('factor_densitometria', 10, 2)->default(1.00);
            $table->decimal('factor_psicoprofilaxis', 10, 2)->default(1.00);
            $table->decimal('factor_otros_servicios', 10, 2)->default(1.00);
            $table->decimal('factor_medicamentos_comerciales', 10, 2)->default(1.00);
            $table->decimal('factor_medicamentos_genericos', 10, 2)->default(1.00);
            $table->decimal('factor_material_medico', 10, 2)->default(1.00);

            $table->date('fecha_creacion')->default(DB::raw('CURRENT_DATE'));

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->foreign('iafa_id')->references('id')->on('iafas')->restrictOnDelete();

            $table->index('estado');
            $table->index('iafa_id');
            $table->index('tarifa_base');
            $table->index('requiere_acreditacion');
            $table->index('descripcion_tarifa');
        });

        DB::statement("ALTER TABLE tarifas ADD CONSTRAINT tarifas_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS tarifas_tarifa_base_unique ON tarifas (tarifa_base) WHERE tarifa_base = TRUE");

        DB::statement(<<<SQL
ALTER TABLE tarifas ADD CONSTRAINT tarifas_factores_check CHECK (
    factor_clinica >= 0 AND
    factor_laboratorio >= 0 AND
    factor_ecografia >= 0 AND
    factor_procedimientos >= 0 AND
    factor_rayos_x >= 0 AND
    factor_tomografia >= 0 AND
    factor_patologia >= 0 AND
    factor_medicina_fisica >= 0 AND
    factor_resonancia >= 0 AND
    factor_honorarios_medicos >= 0 AND
    factor_medicinas >= 0 AND
    factor_equipos_oxigeno >= 0 AND
    factor_banco_sangre >= 0 AND
    factor_mamografia >= 0 AND
    factor_densitometria >= 0 AND
    factor_psicoprofilaxis >= 0 AND
    factor_otros_servicios >= 0 AND
    factor_medicamentos_comerciales >= 0 AND
    factor_medicamentos_genericos >= 0 AND
    factor_material_medico >= 0
)
SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tarifas DROP CONSTRAINT IF EXISTS tarifas_factores_check');
        DB::statement('DROP INDEX IF EXISTS tarifas_tarifa_base_unique');
        DB::statement('ALTER TABLE tarifas DROP CONSTRAINT IF EXISTS tarifas_estado_check');
        Schema::dropIfExists('tarifas');
    }
};
