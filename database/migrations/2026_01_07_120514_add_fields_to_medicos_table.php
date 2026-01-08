<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->string('rne', 20)->nullable()->unique();
            $table->string('direccion', 255)->nullable();
            $table->string('centro_trabajo', 255)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('ruc', 11)->nullable();
            $table->string('telefono_02', 30)->nullable();

            $table->integer('adicionales')->default(0);
            $table->integer('extras')->default(0);
            $table->integer('tiempo_promedio_por_paciente')->default(0);

            $table->index('fecha_nacimiento');
            $table->index('ruc');
        });

        DB::statement('ALTER TABLE medicos ADD CONSTRAINT medicos_adicionales_check CHECK (adicionales >= 0)');
        DB::statement('ALTER TABLE medicos ADD CONSTRAINT medicos_extras_check CHECK (extras >= 0)');
        DB::statement('ALTER TABLE medicos ADD CONSTRAINT medicos_tiempo_promedio_check CHECK (tiempo_promedio_por_paciente >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE medicos DROP CONSTRAINT IF EXISTS medicos_tiempo_promedio_check');
        DB::statement('ALTER TABLE medicos DROP CONSTRAINT IF EXISTS medicos_extras_check');
        DB::statement('ALTER TABLE medicos DROP CONSTRAINT IF EXISTS medicos_adicionales_check');

        Schema::table('medicos', function (Blueprint $table) {
            $table->dropIndex(['fecha_nacimiento']);
            $table->dropIndex(['ruc']);

            $table->dropColumn([
                'rne',
                'direccion',
                'centro_trabajo',
                'fecha_nacimiento',
                'ruc',
                'telefono_02',
                'adicionales',
                'extras',
                'tiempo_promedio_por_paciente',
            ]);
        });
    }
};
