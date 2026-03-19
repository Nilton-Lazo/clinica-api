<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_emergencia', function (Blueprint $table) {
            $table->time('hora_asistencia')->nullable()->after('hora');
            
            $table->foreignId('paciente_plan_id')
                ->nullable()
                ->constrained('paciente_planes')
                ->nullOnDelete()
                ->after('tipo_cliente');
                
            $table->foreignId('tarifa_id')
                ->nullable()
                ->constrained('tarifas')
                ->nullOnDelete()
                ->after('paciente_plan_id');

            $table->string('parentesco_seguro', 30)->nullable()->after('tarifa_id');
            $table->string('titular_nombre', 255)->nullable()->after('parentesco_seguro');
            
            $table->decimal('monto_a_pagar', 14, 2)->default(0)->after('titular_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('registro_emergencia', function (Blueprint $table) {
            $table->dropForeign(['paciente_plan_id']);
            $table->dropForeign(['tarifa_id']);
            
            $table->dropColumn([
                'hora_asistencia',
                'paciente_plan_id',
                'tarifa_id',
                'parentesco_seguro',
                'titular_nombre',
                'monto_a_pagar'
            ]);
        });
    }
};
