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
        Schema::create('application_evaluation_document_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_evaluation_id')
                ->constrained('application_evaluations')
                ->cascadeOnDelete();
            $table->foreignId('application_document_id')
                ->constrained('application_documents')
                ->cascadeOnDelete();
            $table->decimal('pontuacao', 8, 2);
            $table->timestamps();

            $table->unique(['application_evaluation_id', 'application_document_id'], 'aed_scores_eval_doc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_evaluation_document_scores');
    }
};
