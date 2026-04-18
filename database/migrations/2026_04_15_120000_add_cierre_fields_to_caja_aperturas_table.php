<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_aperturas', function (Blueprint $table) {
            $table->decimal('monto_cierre', 15, 2)->nullable()->after('monto_inicio');
            $table->text('observaciones_cierre')->nullable()->after('observaciones');
            $table->timestampTz('cerrada_at')->nullable()->after('apertura_at');

            $table->index(['user_recepciona_id', 'tipo', 'cerrada_at'], 'caja_aperturas_user_tipo_cerrada_idx');
        });
    }

    public function down(): void
    {
        Schema::table('caja_aperturas', function (Blueprint $table) {
            $table->dropIndex('caja_aperturas_user_tipo_cerrada_idx');
            $table->dropColumn(['monto_cierre', 'observaciones_cierre', 'cerrada_at']);
        });
    }
};
