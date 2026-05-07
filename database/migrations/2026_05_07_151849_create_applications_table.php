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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('selection_process_id')->constrained('selection_processes')->cascadeOnDelete();
            $table->string('numero_protocolo')->unique()->nullable();
            $table->string('status', 20)->default('rascunho')->index();
            $table->json('dados_inscricao')->nullable();
            $table->timestamp('finalizada_em')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'selection_process_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
