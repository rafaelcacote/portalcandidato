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
        Schema::create('process_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_process_id')->constrained('selection_processes')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordem');
            $table->string('nome');
            $table->timestamp('inicio_em')->nullable();
            $table->timestamp('fim_em')->nullable();
            $table->timestamps();

            $table->index(['selection_process_id', 'ordem']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_stages');
    }
};
