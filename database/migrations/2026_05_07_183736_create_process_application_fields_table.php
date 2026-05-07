<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('process_application_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_process_id')->constrained('selection_processes')->cascadeOnDelete();
            $table->string('label');
            $table->string('field_key');
            $table->string('tipo', 30)->default('text');
            $table->boolean('obrigatorio')->default(false);
            $table->json('opcoes')->nullable();
            $table->unsignedSmallInteger('ordem')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_application_fields');
    }
};
