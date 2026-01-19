<?php

use App\Core\support\RecordStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('tipo_documento', 30);
            $table->string('numero_documento', 20)->nullable();
            $table->string('nr', 10)->nullable();

            $table->string('nombres', 120)->nullable();
            $table->string('apellido_paterno', 80)->nullable();
            $table->string('apellido_materno', 80)->nullable();

            $table->string('estado_civil', 20)->nullable();
            $table->string('sexo', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();

            $table->char('nacionalidad_iso2', 2)->nullable();
            $table->char('ubigeo_nacimiento', 6)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->char('ubigeo_domicilio', 6)->nullable();

            $table->string('parentesco_seguro', 30)->nullable();
            $table->string('titular_nombre', 200)->nullable();

            $table->string('celular', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();

            $table->unsignedBigInteger('medico_tratante_id')->nullable();

            $table->string('tipo_sangre', 10)->nullable();
            $table->string('tipo_paciente', 20)->nullable();
            $table->string('ocupacion', 30)->nullable();

            $table->string('medio_informacion', 30)->nullable();
            $table->string('medio_informacion_detalle', 255)->nullable();

            $table->string('ubicacion_archivo_hc', 255)->nullable();

            $table->string('estado', 12)->default(RecordStatus::ACTIVO->value);

            $table->timestamps();

            $table->foreign('nacionalidad_iso2')->references('iso2')->on('paises');
            $table->foreign('ubigeo_nacimiento')->references('codigo')->on('ubigeos');
            $table->foreign('ubigeo_domicilio')->references('codigo')->on('ubigeos');
            $table->foreign('medico_tratante_id')->references('id')->on('medicos');
        });

        DB::statement("CREATE UNIQUE INDEX pacientes_doc_unique ON pacientes (tipo_documento, numero_documento) WHERE tipo_documento <> 'SIN_DOCUMENTO' AND numero_documento IS NOT NULL");
        DB::statement("CREATE UNIQUE INDEX pacientes_nr_unique ON pacientes (nr) WHERE nr IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
