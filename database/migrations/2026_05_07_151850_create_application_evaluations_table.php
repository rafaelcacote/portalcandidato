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
        Schema::create('application_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('aguardando');
            $table->string('resultado', 30)->nullable();
            $table->text('observacoes')->nullable();
            $table->decimal('pontuacao_total', 10, 2)->default(0);
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'evaluator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_evaluations');
    }
};
