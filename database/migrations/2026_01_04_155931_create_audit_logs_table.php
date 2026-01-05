<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_type', 50)->default('user'); 
            $table->string('actor_username', 100)->nullable()->index();
            $table->string('actor_nivel', 50)->nullable()->index();

            $table->string('action', 120)->index();        
            $table->string('action_label', 180)->nullable(); 

            $table->string('entity_type', 150)->nullable()->index();
            $table->string('entity_id', 64)->nullable()->index();   

            $table->string('module', 80)->index();          
            $table->string('route', 255)->index();           
            $table->string('http_method', 10)->index();     
            $table->uuid('request_id')->index();           

            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();

            $table->string('result', 20)->index(); 
            $table->unsignedSmallInteger('status_code')->nullable()->index();

            $table->jsonb('metadata')->nullable();

            $table->timestampsTz();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['module', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
