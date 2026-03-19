<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_emergencia', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_emergencia_id')->nullable()->after('hora_asistencia');
            $table->unsignedBigInteger('topico_id')->nullable()->after('tipo_emergencia_id');
            $table->unsignedBigInteger('medico_emergencia_id')->nullable()->after('topico_id');
            
            $table->string('diagnostico_ingreso', 500)->nullable()->after('titular_nombre');
            
            // SOAT Fields
            $table->boolean('soat_activo')->default(false)->after('diagnostico_ingreso');
            $table->unsignedBigInteger('soat_tipo_documento_id')->nullable()->after('soat_activo');
            $table->string('soat_numero_documento', 100)->nullable()->after('soat_tipo_documento_id');
            $table->string('soat_titular_referencia', 255)->nullable()->after('soat_numero_documento');
            $table->string('soat_poliza', 100)->nullable()->after('soat_titular_referencia');
            $table->string('soat_placa', 50)->nullable()->after('soat_poliza');
            $table->string('soat_siniestro', 255)->nullable()->after('soat_placa');
            $table->string('soat_tipo_accidente', 255)->nullable()->after('soat_siniestro');
            $table->string('soat_lugar_accidente', 255)->nullable()->after('soat_tipo_accidente');
            $table->string('soat_dni_conductor', 50)->nullable()->after('soat_lugar_accidente');
            $table->string('soat_apellido_paterno_conductor', 255)->nullable()->after('soat_dni_conductor');
            $table->string('soat_apellido_materno_conductor', 255)->nullable()->after('soat_apellido_paterno_conductor');
            $table->string('soat_contacto_conductor', 100)->nullable()->after('soat_apellido_materno_conductor');
            $table->date('soat_fecha_siniestro')->nullable()->after('soat_contacto_conductor');
            $table->string('soat_hora_siniestro', 20)->nullable()->after('soat_fecha_siniestro');
            $table->string('soat_datos_intervencion_autoridad', 500)->nullable()->after('soat_hora_siniestro');
            $table->unsignedBigInteger('soat_documento_atencion_id_1')->nullable()->after('soat_datos_intervencion_autoridad');
            $table->string('soat_numero_documento_atencion_1', 100)->nullable()->after('soat_documento_atencion_id_1');
            $table->unsignedBigInteger('soat_documento_atencion_id_2')->nullable()->after('soat_numero_documento_atencion_1');
            $table->string('soat_numero_documento_atencion_2', 100)->nullable()->after('soat_documento_atencion_id_2');
        });
    }

    public function down(): void
    {
        Schema::table('registro_emergencia', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_emergencia_id',
                'topico_id',
                'medico_emergencia_id',
                'diagnostico_ingreso',
                'soat_activo',
                'soat_tipo_documento_id',
                'soat_numero_documento',
                'soat_titular_referencia',
                'soat_poliza',
                'soat_placa',
                'soat_siniestro',
                'soat_tipo_accidente',
                'soat_lugar_accidente',
                'soat_dni_conductor',
                'soat_apellido_paterno_conductor',
                'soat_apellido_materno_conductor',
                'soat_contacto_conductor',
                'soat_fecha_siniestro',
                'soat_hora_siniestro',
                'soat_datos_intervencion_autoridad',
                'soat_documento_atencion_id_1',
                'soat_numero_documento_atencion_1',
                'soat_documento_atencion_id_2',
                'soat_numero_documento_atencion_2',
            ]);
        });
    }
};
