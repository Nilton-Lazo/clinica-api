<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pacientes_documento_estado ON pacientes (numero_documento, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pacientes_nr_estado ON pacientes (nr, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pacientes_nombres_trgm ON pacientes USING gin (nombres gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pacientes_apellido_paterno_trgm ON pacientes USING gin (apellido_paterno gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pacientes_apellido_materno_trgm ON pacientes USING gin (apellido_materno gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pacientes_documento_trgm ON pacientes USING gin (numero_documento gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pacientes_nr_trgm ON pacientes USING gin (nr gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_paciente_planes_paciente_estado_id ON paciente_planes (paciente_id, estado, id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_paciente_planes_paciente_fecha ON paciente_planes (paciente_id, fecha_afiliacion)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_paciente_planes_parentesco_trgm ON paciente_planes USING gin (parentesco_seguro gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_medicos_estado_codigo ON medicos (estado, codigo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medicos_dni_trgm ON medicos USING gin (dni gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medicos_cmp_trgm ON medicos USING gin (cmp gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medicos_rne_trgm ON medicos USING gin (rne gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medicos_nombres_trgm ON medicos USING gin (nombres gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medicos_apellido_paterno_trgm ON medicos USING gin (apellido_paterno gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medicos_apellido_materno_trgm ON medicos USING gin (apellido_materno gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_iafas_estado_codigo ON iafas (estado, codigo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_iafas_razon_social_trgm ON iafas USING gin (razon_social gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_iafas_descripcion_corta_trgm ON iafas USING gin (descripcion_corta gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_iafas_ruc_trgm ON iafas USING gin (ruc gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_contratantes_estado_codigo ON contratantes (estado, codigo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_contratantes_razon_social_trgm ON contratantes USING gin (razon_social gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_contratantes_ruc_trgm ON contratantes USING gin (ruc gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_tarifas_estado_codigo ON tarifas (estado, codigo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_tarifas_descripcion_trgm ON tarifas USING gin (descripcion_tarifa gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_tipos_clientes_estado_codigo ON tipos_clientes (estado, codigo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_tipos_clientes_tarifa ON tipos_clientes (tarifa_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_tipos_clientes_descripcion_trgm ON tipos_clientes USING gin (descripcion_tipo_cliente gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_clientes_estado_codigo ON clientes (estado, codigo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_clientes_nombre_trgm ON clientes USING gin (nombre gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_clientes_dni_ruc_trgm ON clientes USING gin (dni_o_ruc gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_registro_emergencia_fecha_orden ON registro_emergencia (fecha, orden, id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_registro_emergencia_numero_cuenta ON registro_emergencia (numero_cuenta)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_registro_emergencia_hc_trgm ON registro_emergencia USING gin (numero_hc gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_registro_emergencia_nombre_trgm ON registro_emergencia USING gin (apellidos_nombres gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_registro_emergencia_cuenta_trgm ON registro_emergencia USING gin (numero_cuenta gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_cuentas_origen_origen_id ON cuentas (origen, origen_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_cuentas_fecha_origen ON cuentas (fecha, origen)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_cuentas_nro_cuenta_trgm ON cuentas USING gin (nro_cuenta gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_cuentas_paciente_nombre_trgm ON cuentas USING gin (paciente_nombre gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_tarifa_servicios_tarifa_estado_codigo ON tarifa_servicios (tarifa_id, estado, codigo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_tarifa_servicios_descripcion_trgm ON tarifa_servicios USING gin (descripcion gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_tarifa_servicios_nomenclador_trgm ON tarifa_servicios USING gin (nomenclador gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_caja_emision_apertura_numeracion_created ON caja_emision_comprobantes (caja_apertura_id, numeracion_comprobante_id, created_at, id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_caja_emision_nro_cuenta_created ON caja_emision_comprobantes (nro_cuenta, created_at)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_ubigeos_codigo_trgm ON ubigeos USING gin ((codigo::text) gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ubigeos_departamento_trgm ON ubigeos USING gin (departamento gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ubigeos_provincia_trgm ON ubigeos USING gin (provincia gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ubigeos_distrito_trgm ON ubigeos USING gin (distrito gin_trgm_ops)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'idx_ubigeos_distrito_trgm',
            'idx_ubigeos_provincia_trgm',
            'idx_ubigeos_departamento_trgm',
            'idx_ubigeos_codigo_trgm',
            'idx_caja_emision_nro_cuenta_created',
            'idx_caja_emision_apertura_numeracion_created',
            'idx_tarifa_servicios_nomenclador_trgm',
            'idx_tarifa_servicios_descripcion_trgm',
            'idx_tarifa_servicios_tarifa_estado_codigo',
            'idx_cuentas_paciente_nombre_trgm',
            'idx_cuentas_nro_cuenta_trgm',
            'idx_cuentas_fecha_origen',
            'idx_cuentas_origen_origen_id',
            'idx_registro_emergencia_cuenta_trgm',
            'idx_registro_emergencia_nombre_trgm',
            'idx_registro_emergencia_hc_trgm',
            'idx_registro_emergencia_numero_cuenta',
            'idx_registro_emergencia_fecha_orden',
            'idx_clientes_dni_ruc_trgm',
            'idx_clientes_nombre_trgm',
            'idx_clientes_estado_codigo',
            'idx_tipos_clientes_descripcion_trgm',
            'idx_tipos_clientes_tarifa',
            'idx_tipos_clientes_estado_codigo',
            'idx_tarifas_descripcion_trgm',
            'idx_tarifas_estado_codigo',
            'idx_contratantes_ruc_trgm',
            'idx_contratantes_razon_social_trgm',
            'idx_contratantes_estado_codigo',
            'idx_iafas_ruc_trgm',
            'idx_iafas_descripcion_corta_trgm',
            'idx_iafas_razon_social_trgm',
            'idx_iafas_estado_codigo',
            'idx_medicos_apellido_materno_trgm',
            'idx_medicos_apellido_paterno_trgm',
            'idx_medicos_nombres_trgm',
            'idx_medicos_rne_trgm',
            'idx_medicos_cmp_trgm',
            'idx_medicos_dni_trgm',
            'idx_medicos_estado_codigo',
            'idx_paciente_planes_parentesco_trgm',
            'idx_paciente_planes_paciente_fecha',
            'idx_paciente_planes_paciente_estado_id',
            'idx_pacientes_nr_trgm',
            'idx_pacientes_documento_trgm',
            'idx_pacientes_apellido_materno_trgm',
            'idx_pacientes_apellido_paterno_trgm',
            'idx_pacientes_nombres_trgm',
            'idx_pacientes_nr_estado',
            'idx_pacientes_documento_estado',
        ] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }
};
