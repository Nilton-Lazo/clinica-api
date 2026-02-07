<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indicadores de atención: 1 = marcado, 0 = no marcado.
     */
    public function up(): void
    {
        Schema::table('cita_atenciones', function (Blueprint $table) {
            $table->boolean('control_pre_post_natal')->default(false)->after('titular_nombre');
            $table->boolean('control_nino_sano')->default(false)->after('control_pre_post_natal');
            $table->boolean('chequeo')->default(false)->after('control_nino_sano');
            $table->boolean('carencia')->default(false)->after('chequeo');
            $table->boolean('latencia')->default(false)->after('carencia');
        });
    }

    public function down(): void
    {
        Schema::table('cita_atenciones', function (Blueprint $table) {
            $table->dropColumn([
                'control_pre_post_natal',
                'control_nino_sano',
                'chequeo',
                'carencia',
                'latencia',
            ]);
        });
    }
};
