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
        Schema::table('process_evaluator_assignments', function (Blueprint $table) {
            $table->boolean('pode_avaliar')->default(true)->after('user_id');
            $table->boolean('pode_visualizar_resultados')->default(false)->after('pode_avaliar');
            $table->boolean('pode_baixar_documentos')->default(true)->after('pode_visualizar_resultados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_evaluator_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'pode_avaliar',
                'pode_visualizar_resultados',
                'pode_baixar_documentos',
            ]);
        });
    }
};
