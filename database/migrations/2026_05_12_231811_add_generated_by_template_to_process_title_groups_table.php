<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_title_groups', function (Blueprint $table) {
            $table->boolean('generated_by_template')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('process_title_groups', function (Blueprint $table) {
            $table->dropColumn('generated_by_template');
        });
    }
};
