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
        Schema::table('tipo_documentos', function (Blueprint $table) {
            $table->string('codigo', 64)->nullable()->unique()->after('id');
        });

        Schema::table('selection_processes', function (Blueprint $table) {
            $table->string('tipo_programa', 20)->nullable()->after('status');
        });

        Schema::table('process_required_documents', function (Blueprint $table) {
            $table->boolean('gerado_por_template')->default(false)->after('obrigatorio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_required_documents', function (Blueprint $table) {
            $table->dropColumn('gerado_por_template');
        });

        Schema::table('selection_processes', function (Blueprint $table) {
            $table->dropColumn('tipo_programa');
        });

        Schema::table('tipo_documentos', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->dropColumn('codigo');
        });
    }
};
