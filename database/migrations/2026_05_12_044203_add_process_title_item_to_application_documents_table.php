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
        Schema::table('application_documents', function (Blueprint $table): void {
            $table->foreignId('process_title_item_id')
                ->nullable()
                ->after('process_required_document_id')
                ->constrained('process_title_items')
                ->nullOnDelete();

            $table->unsignedSmallInteger('quantidade')->nullable()->after('process_title_item_id');

            $table->index(['application_id', 'process_title_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table): void {
            $table->dropIndex(['application_id', 'process_title_item_id']);
            $table->dropConstrainedForeignId('process_title_item_id');
            $table->dropColumn('quantidade');
        });
    }
};
