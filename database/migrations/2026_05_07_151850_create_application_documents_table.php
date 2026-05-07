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
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('process_required_document_id')->constrained('process_required_documents')->cascadeOnDelete();
            $table->string('caminho');
            $table->string('nome_arquivo');
            $table->string('mime', 120);
            $table->unsignedSmallInteger('versao')->default(1);
            $table->string('status', 20)->default('enviado')->index();
            $table->text('motivo_recusa')->nullable();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_em')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'process_required_document_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
