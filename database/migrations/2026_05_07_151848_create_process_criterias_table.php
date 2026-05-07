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
        Schema::create('process_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_process_id')->constrained('selection_processes')->cascadeOnDelete();
            $table->string('nome');
            $table->decimal('peso', 8, 2)->default(1);
            $table->decimal('pontuacao_max', 8, 2);
            $table->unsignedSmallInteger('ordem')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_criteria');
    }
};
