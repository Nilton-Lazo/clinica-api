<?php

use App\Core\support\RecordStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paciente_contactos_emergencia', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('paciente_id');

            $table->string('nombres', 120)->nullable();
            $table->string('apellido_paterno', 80)->nullable();
            $table->string('apellido_materno', 80)->nullable();
            $table->string('parentesco_emergencia', 30)->nullable();

            $table->string('celular', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('observaciones', 255)->nullable();

            $table->string('estado', 12)->default(RecordStatus::ACTIVO->value);

            $table->timestamps();

            $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('cascade');
            $table->unique('paciente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_contactos_emergencia');
    }
};
