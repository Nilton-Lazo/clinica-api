<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Servicios solicitados por atención de cita (copagos, descuentos, médico, usuario).
     */
    public function up(): void
    {
        Schema::create('cita_atencion_servicios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cita_atencion_id')
                ->constrained('cita_atenciones')
                ->cascadeOnDelete();

            $table->foreignId('tarifa_servicio_id')
                ->constrained('tarifa_servicios')
                ->restrictOnDelete();

            $table->foreignId('medico_id')
                ->constrained('medicos')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('cop_var', 14, 3)->default(0);
            $table->decimal('cop_fijo', 14, 3)->default(0);
            $table->decimal('descuento_pct', 8, 3)->default(0);
            $table->decimal('aumento_pct', 8, 3)->default(0);
            $table->decimal('cantidad', 14, 3)->default(1);

            $table->decimal('precio_sin_igv', 14, 3)->default(0);
            $table->decimal('precio_con_igv', 14, 3)->default(0);

            $table->timestamps();

            $table->index(['cita_atencion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cita_atencion_servicios');
    }
};
