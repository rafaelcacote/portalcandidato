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
            $table->dropForeign(['process_required_document_id']);
        });

        Schema::table('application_documents', function (Blueprint $table): void {
            $table->string('candidatura_document_kind', 50)->nullable()->after('application_id');
            $table->unsignedBigInteger('process_required_document_id')->nullable()->change();
        });

        Schema::table('application_documents', function (Blueprint $table): void {
            $table->foreign('process_required_document_id')
                ->references('id')
                ->on('process_required_documents')
                ->nullOnDelete();
        });

        Schema::table('application_documents', function (Blueprint $table): void {
            $table->index(['application_id', 'candidatura_document_kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table): void {
            $table->dropIndex(['application_id', 'candidatura_document_kind']);
        });

        Schema::table('application_documents', function (Blueprint $table): void {
            $table->dropForeign(['process_required_document_id']);
        });

        Schema::table('application_documents', function (Blueprint $table): void {
            $table->dropColumn('candidatura_document_kind');
        });

        Schema::table('application_documents', function (Blueprint $table): void {
            $table->unsignedBigInteger('process_required_document_id')->nullable(false)->change();
        });

        Schema::table('application_documents', function (Blueprint $table): void {
            $table->foreign('process_required_document_id')
                ->references('id')
                ->on('process_required_documents')
                ->cascadeOnDelete();
        });
    }
};
