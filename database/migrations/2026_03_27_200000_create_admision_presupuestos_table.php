<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admision_presupuestos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('paciente_plan_id')->constrained('paciente_planes')->cascadeOnDelete();
            $table->foreignId('tarifa_id')->nullable()->constrained('tarifas')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();

            $table->date('vigencia_hasta');
            $table->string('estado', 20);
            $table->decimal('monto_a_pagar', 14, 4);
            $table->json('payload');

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['paciente_id', 'created_at']);
        });

        DB::statement("ALTER TABLE admision_presupuestos ADD CONSTRAINT admision_presupuestos_estado_check CHECK (estado IN ('VIGENTE','UTILIZADO','VENCIDO','ANULADO'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE admision_presupuestos DROP CONSTRAINT IF EXISTS admision_presupuestos_estado_check');
        Schema::dropIfExists('admision_presupuestos');
    }
};
