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
        Schema::create('application_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_evaluation_id')->constrained('application_evaluations')->cascadeOnDelete();
            $table->foreignId('process_criteria_id')->constrained('process_criteria')->cascadeOnDelete();
            $table->decimal('pontuacao', 8, 2);
            $table->timestamps();

            $table->unique(['application_evaluation_id', 'process_criteria_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_evaluation_scores');
    }
};
