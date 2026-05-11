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
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto_path', 500)->nullable()->after('password');
            $table->string('identidade', 32)->nullable()->after('foto_path');
            $table->string('orgao_emissor', 50)->nullable()->after('identidade');
            $table->string('identidade_uf', 2)->nullable()->after('orgao_emissor');
            $table->date('identidade_data_emissao')->nullable()->after('identidade_uf');
            $table->string('naturalidade', 120)->nullable()->after('identidade_data_emissao');
            $table->string('nacionalidade', 120)->nullable()->after('naturalidade');
            $table->string('sexo', 30)->nullable()->after('nacionalidade');
            $table->string('endereco', 255)->nullable()->after('sexo');
            $table->string('endereco_numero', 20)->nullable()->after('endereco');
            $table->string('bairro', 120)->nullable()->after('endereco_numero');
            $table->string('cep', 9)->nullable()->after('bairro');
            $table->string('cidade', 120)->nullable()->after('cep');
            $table->string('endereco_uf', 2)->nullable()->after('cidade');
            $table->string('pais', 120)->nullable()->after('endereco_uf');
            $table->string('telefone_fixo', 25)->nullable()->after('telefone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'foto_path',
                'identidade',
                'orgao_emissor',
                'identidade_uf',
                'identidade_data_emissao',
                'naturalidade',
                'nacionalidade',
                'sexo',
                'endereco',
                'endereco_numero',
                'bairro',
                'cep',
                'cidade',
                'endereco_uf',
                'pais',
                'telefone_fixo',
            ]);
        });
    }
};
