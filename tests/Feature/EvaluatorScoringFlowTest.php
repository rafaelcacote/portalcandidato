<?php

use App\Models\Modules\Admin\Models\ProcessCriteria;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('evaluator can score candidate application', function () {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $criteria = ProcessCriteria::query()->create([
        'selection_process_id' => $process->id,
        'nome' => 'Experiência',
        'peso' => 1,
        'pontuacao_max' => 30,
        'ordem' => 1,
    ]);

    $application = Application::query()->create(evaluableApplicationAttributes([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'em_analise',
    ]));

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.score.store', $application), [
            'scores' => [
                [
                    'process_criteria_id' => $criteria->id,
                    'pontuacao' => 20,
                ],
            ],
            'document_scores' => [],
            'resultado' => 'classificado',
        ])
        ->assertRedirect();

    $evaluation = ApplicationEvaluation::query()->firstOrFail();
    expect((float) $evaluation->pontuacao_total)->toBe(20.0);
});

test('evaluator candidate review includes photo url and can view photo', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    Storage::fake('public');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $path = UploadedFile::fake()->image('foto.jpg')->store('candidate-photos/'.$candidate->id, 'public');
    $candidate->forceFill(['foto_path' => $path])->save();

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create(evaluableApplicationAttributes([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'em_analise',
    ]));

    $this->actingAs($evaluator)
        ->get(route('evaluator.candidates.show', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('application.user.photo_url')
            ->where('application.user.photo_url', fn ($url) => is_string($url) && $url !== ''));

    $this->actingAs($evaluator)
        ->get(route('evaluator.candidates.photo', $application))
        ->assertOk();
});

test('evaluator candidate review includes research line and advisor summary', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create(evaluableApplicationAttributes([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'em_analise',
        'dados_inscricao' => [
            'step_3' => validApplicationStep3Payload(),
        ],
    ]));

    $this->actingAs($evaluator)
        ->get(route('evaluator.candidates.show', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('application.research_line_summary.linha_pesquisa', 'linha_1')
            ->where('application.research_line_summary.orientador', 'Dr. Aldalice Aguiar de Souza')
            ->where(
                'application.research_line_summary.linha_pesquisa_label',
                'Linha de Pesquisa 1 - Tecnologias Sociais e Educativas como Instrumentos para Promoção da Saúde',
            ));
});

test('evaluator candidate review includes employment relationship summary', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create(evaluableApplicationAttributes([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'em_analise',
        'dados_inscricao' => [
            'step_2' => ['concorre_vagas_sem_vinculo' => true],
        ],
    ]));

    $this->actingAs($evaluator)
        ->get(route('evaluator.candidates.show', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('application.employment_relationship_summary.concorre_vagas_sem_vinculo', true)
            ->where('application.employment_relationship_summary.resposta_label', 'Sim'));
});
