<?php

namespace Database\Seeders;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use App\Modules\Shared\Enums\SelectionProcessProgramType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CandidateAndSelectionProcessSeeder extends Seeder
{
    /**
     * Cria um usuário candidato (perfil completo) e um processo seletivo ativo para desenvolvimento.
     *
     * Credenciais: candidato@portalcandidato.local / password
     */
    public function run(): void
    {
        $candidate = User::query()->where('email', 'candidato@portalcandidato.local')->first();

        if ($candidate === null) {
            $candidate = User::factory()
                ->completeCandidateProfile()
                ->create([
                    'name' => 'Candidato Demo',
                    'email' => 'candidato@portalcandidato.local',
                    'password' => Hash::make('password'),
                    'ativo' => true,
                    'email_verified_at' => now(),
                ]);
        }

        $candidate->syncRoles(['candidato']);

        SelectionProcess::query()->firstOrCreate(
            ['titulo' => 'Processo Seletivo Demo — Mestrado'],
            [
                'descricao' => 'Processo criado pelo seed para desenvolvimento e testes manuais.',
                'regras' => 'Inscrições conforme calendário definido no seed.',
                'status' => 'ativo',
                'tipo_programa' => SelectionProcessProgramType::Mestrado,
                'inscricao_inicio_em' => now()->subDays(7),
                'inscricao_fim_em' => now()->addMonths(3),
            ],
        );
    }
}
