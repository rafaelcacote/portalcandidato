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
        Schema::table('selection_processes', function (Blueprint $table) {
            $table->string('edital_pdf_path', 500)->nullable()->after('tipo_programa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selection_processes', function (Blueprint $table) {
            $table->dropColumn('edital_pdf_path');
        });
    }
};
