<?php

use App\Core\support\RecordStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinica', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 200);
            $table->string('ruc', 20)->nullable();
            $table->string('direccion', 500)->nullable();
            $table->string('telefono', 40)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('sitio_web', 255)->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('estado', 12)->default(RecordStatus::ACTIVO->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinica');
    }
};
