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
        Schema::table('process_required_documents', function (Blueprint $table) {
            $table->foreignId('tipo_documento_id')
                ->nullable()
                ->after('selection_process_id')
                ->constrained('tipo_documentos')
                ->nullOnDelete();
            $table->foreignId('tipo_titulo_id')
                ->nullable()
                ->after('tipo_documento_id')
                ->constrained('tipo_titulos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_required_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_documento_id');
            $table->dropConstrainedForeignId('tipo_titulo_id');
        });
    }
};
