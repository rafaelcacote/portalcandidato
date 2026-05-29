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
        Schema::table('process_stages', function (Blueprint $table) {
            $table->timestamp('recurso_inicio_em')->nullable()->after('fim_em');
            $table->timestamp('recurso_fim_em')->nullable()->after('recurso_inicio_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_stages', function (Blueprint $table) {
            $table->dropColumn(['recurso_inicio_em', 'recurso_fim_em']);
        });
    }
};
