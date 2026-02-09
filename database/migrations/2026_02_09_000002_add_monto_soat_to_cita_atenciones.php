<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Monto a pagar (total de la atención) y datos SOAT (accidentes).
     */
    public function up(): void
    {
        Schema::table('cita_atenciones', function (Blueprint $table) {
            $table->decimal('monto_a_pagar', 14, 4)->default(0)->after('latencia')->comment('Total a pagar de la atención (S/)');
            $table->boolean('soat_activo')->default(false)->after('monto_a_pagar')->comment('Atención por accidente SOAT');
            $table->string('soat_numero_poliza', 50)->nullable()->after('soat_activo');
            $table->string('soat_numero_placa', 20)->nullable()->after('soat_numero_poliza');
        });
    }

    public function down(): void
    {
        Schema::table('cita_atenciones', function (Blueprint $table) {
            $table->dropColumn(['monto_a_pagar', 'soat_activo', 'soat_numero_poliza', 'soat_numero_placa']);
        });
    }
};
