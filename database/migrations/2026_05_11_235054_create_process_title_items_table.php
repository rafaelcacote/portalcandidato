<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_title_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_title_group_id')
                ->constrained('process_title_groups')
                ->cascadeOnDelete();
            $table->string('code', 20);
            $table->text('title');
            $table->decimal('score_per_unit', 8, 2);
            $table->string('score_unit', 60);
            $table->unsignedSmallInteger('max_quantity')->nullable();
            $table->string('period_rule', 120)->nullable();
            $table->boolean('requires_attachment')->default(true);
            $table->json('accepted_formats')->nullable();
            $table->unsignedSmallInteger('max_file_size_mb')->default(10);
            $table->text('candidate_instructions')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['process_title_group_id', 'code'], 'process_title_items_group_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_title_items');
    }
};
