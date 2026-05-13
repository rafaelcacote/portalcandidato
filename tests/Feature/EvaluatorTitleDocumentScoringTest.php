<?php

use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluationDocumentScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('evaluator can save pontuação for title documents', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS com titulação',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Formação Acadêmica / Titulação',
        'max_score' => 5.0,
        'is_active' => true,
    ]);

    $item = ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'A.1',
        'title' => 'Especialização',
        'score_per_unit' => 2.5,
        'score_unit' => 'por título',
        'max_quantity' => null,
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'is_active' => true,
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'em_analise',
    ]);

    $document = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_required_document_id' => null,
        'process_title_item_id' => $item->id,
        'quantidade' => 1,
        'caminho' => 'private/test/doc.pdf',
        'nome_arquivo' => 'doc.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.score.store', $application), [
            'scores' => [],
            'document_scores' => [
                [
                    'application_document_id' => $document->id,
                    'pontuacao' => 2.5,
                ],
            ],
            'resultado' => 'classificado',
        ])
        ->assertRedirect();

    $evaluation = ApplicationEvaluation::query()->firstOrFail();
    expect((float) $evaluation->pontuacao_total)->toBe(2.5)
        ->and(ApplicationEvaluationDocumentScore::query()->count())->toBe(1);
});

test('evaluator cannot exceed per-row max for a title document', function (): void {
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

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Titulação',
        'max_score' => 10.0,
        'is_active' => true,
    ]);

    $item = ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'A.1',
        'title' => 'Curso',
        'score_per_unit' => 1.0,
        'score_unit' => 'por título',
        'max_quantity' => 2,
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'is_active' => true,
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'em_analise',
    ]);

    $document = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_required_document_id' => null,
        'process_title_item_id' => $item->id,
        'quantidade' => 2,
        'caminho' => 'private/test/doc.pdf',
        'nome_arquivo' => 'doc.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.score.store', $application), [
            'scores' => [],
            'document_scores' => [
                [
                    'application_document_id' => $document->id,
                    'pontuacao' => 5,
                ],
            ],
            'resultado' => 'classificado',
        ])
        ->assertSessionHasErrors('document_scores.0.pontuacao');
});

test('evaluator cannot exceed group max_score across title documents', function (): void {
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

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Titulação',
        'max_score' => 3.0,
        'is_active' => true,
    ]);

    $itemA = ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'A.1',
        'title' => 'Curso A',
        'score_per_unit' => 2.0,
        'score_unit' => 'por título',
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'is_active' => true,
    ]);

    $itemB = ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'A.2',
        'title' => 'Curso B',
        'score_per_unit' => 2.0,
        'score_unit' => 'por título',
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'is_active' => true,
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'em_analise',
    ]);

    $docA = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_required_document_id' => null,
        'process_title_item_id' => $itemA->id,
        'quantidade' => 1,
        'caminho' => 'private/test/a.pdf',
        'nome_arquivo' => 'a.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $docB = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_required_document_id' => null,
        'process_title_item_id' => $itemB->id,
        'quantidade' => 1,
        'caminho' => 'private/test/b.pdf',
        'nome_arquivo' => 'b.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.score.store', $application), [
            'scores' => [],
            'document_scores' => [
                ['application_document_id' => $docA->id, 'pontuacao' => 2.0],
                ['application_document_id' => $docB->id, 'pontuacao' => 2.0],
            ],
            'resultado' => 'classificado',
        ])
        ->assertSessionHasErrors('document_scores');
});
