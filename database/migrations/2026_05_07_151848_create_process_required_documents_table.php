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
        Schema::create('process_required_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_process_id')->constrained('selection_processes')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->json('formatos_aceitos')->nullable();
            $table->unsignedSmallInteger('tamanho_max_mb')->default(10);
            $table->boolean('obrigatorio')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_required_documents');
    }
};
