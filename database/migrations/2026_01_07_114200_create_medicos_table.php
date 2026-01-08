<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 10)->unique();
            $table->string('nombres', 120);
            $table->string('apellido_paterno', 120);
            $table->string('apellido_materno', 120);

            $table->foreignId('especialidad_id')->constrained('especialidades');
            $table->string('tipo_profesional_clinica', 10)->default('STAFF');

            $table->string('dni', 20)->nullable()->index();
            $table->string('cmp', 20)->nullable()->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 255)->nullable();

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->index('estado');
            $table->index('codigo');
            $table->index(['apellido_paterno', 'apellido_materno', 'nombres']);
        });

        DB::statement("ALTER TABLE medicos ADD CONSTRAINT medicos_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
        DB::statement("ALTER TABLE medicos ADD CONSTRAINT medicos_tipo_profesional_check CHECK (tipo_profesional_clinica IN ('STAFF','EXTERNO'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE medicos DROP CONSTRAINT IF EXISTS medicos_tipo_profesional_check');
        DB::statement('ALTER TABLE medicos DROP CONSTRAINT IF EXISTS medicos_estado_check');
        Schema::dropIfExists('medicos');
    }
};
