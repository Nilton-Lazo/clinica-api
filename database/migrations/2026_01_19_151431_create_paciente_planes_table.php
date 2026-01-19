<?php

use App\Core\support\RecordStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paciente_planes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('tipo_cliente_id');

            $table->string('parentesco_seguro', 30)->nullable();
            $table->date('fecha_afiliacion');
            $table->string('estado', 12)->default(RecordStatus::ACTIVO->value);

            $table->timestamps();

            $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('cascade');
            $table->foreign('tipo_cliente_id')->references('id')->on('tipos_clientes');

            $table->unique(['paciente_id', 'tipo_cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_planes');
    }
};
