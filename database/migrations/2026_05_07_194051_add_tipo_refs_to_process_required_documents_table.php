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
            // Migration kept intentionally empty to preserve history.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_required_documents', function (Blueprint $table) {
            // Migration kept intentionally empty to preserve history.
        });
    }
};
