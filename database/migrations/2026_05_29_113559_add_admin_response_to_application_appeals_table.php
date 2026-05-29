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
        Schema::table('application_appeals', function (Blueprint $table) {
            $table->text('resposta')->nullable()->after('status');
            $table->foreignId('respondido_por')->nullable()->after('resposta')->constrained('users')->nullOnDelete();
            $table->timestamp('respondido_em')->nullable()->after('respondido_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_appeals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('respondido_por');
            $table->dropColumn(['resposta', 'respondido_em']);
        });
    }
};
