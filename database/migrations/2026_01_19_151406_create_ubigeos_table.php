<?php

use App\Core\support\RecordStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubigeos', function (Blueprint $table) {
            $table->char('codigo', 6)->primary();
            $table->string('departamento', 60);
            $table->string('provincia', 60);
            $table->string('distrito', 60);
            $table->string('estado', 12)->default(RecordStatus::ACTIVO->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubigeos');
    }
};
