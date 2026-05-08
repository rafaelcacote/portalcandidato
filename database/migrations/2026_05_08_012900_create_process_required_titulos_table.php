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
        Schema::create('process_required_titulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_process_id')
                ->constrained('selection_processes')
                ->cascadeOnDelete();
            $table->foreignId('tipo_titulo_id')
                ->constrained('tipo_titulos')
                ->cascadeOnDelete();
            $table->decimal('pontuacao_max', 8, 2)->default(0);
            $table->unsignedSmallInteger('qtd_maxima')->nullable();
            $table->boolean('obrigatorio')->default(false);
            $table->json('formatos_aceitos')->nullable();
            $table->unsignedSmallInteger('tamanho_max_mb')->default(10);
            $table->text('descricao')->nullable();
            $table->timestamps();

            $table->unique(['selection_process_id', 'tipo_titulo_id'], 'process_required_titulos_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_required_titulos');
    }
};
