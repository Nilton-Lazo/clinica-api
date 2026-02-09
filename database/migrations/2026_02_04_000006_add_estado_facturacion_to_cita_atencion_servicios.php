<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado de facturación del servicio: PENDIENTE | FACTURADO.
     */
    public function up(): void
    {
        Schema::table('cita_atencion_servicios', function (Blueprint $table) {
            $table->string('estado_facturacion', 20)->default('PENDIENTE')->after('precio_con_igv');
        });
    }

    public function down(): void
    {
        Schema::table('cita_atencion_servicios', function (Blueprint $table) {
            $table->dropColumn('estado_facturacion');
        });
    }
};
