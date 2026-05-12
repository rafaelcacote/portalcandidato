<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_title_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_process_id')
                ->constrained('selection_processes')
                ->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('max_score', 8, 2)->default(0);
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['selection_process_id', 'code'], 'process_title_groups_process_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_title_groups');
    }
};
