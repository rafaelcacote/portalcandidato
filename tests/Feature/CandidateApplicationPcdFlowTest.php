<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('candidate cannot upload pcd document before saving concorre_vagas_pcd as true', function () {
    Storage::fake('local');
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital PcD',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $file = UploadedFile::fake()->create('decl.pdf', 100, 'application/pdf');

    $this->actingAs($candidate)
        ->post(route('candidate.documents.store', $application), [
            'candidatura_document_kind' => 'pcd_declaracao',
            'arquivo' => $file,
        ])
        ->assertSessionHasErrors('candidatura_document_kind');

    expect(ApplicationDocument::query()->count())->toBe(0);
});

test('candidate cannot submit when concorre_vagas_pcd is true without both pcd documents', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital PcD',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
        'dados_inscricao' => [
            'step_1' => ['concorre_vagas_pcd' => true],
            'step_2' => ['concorre_vagas_sem_vinculo' => false],
            'step_3' => validApplicationStep3Payload(),
        ],
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.submit', $application))
        ->assertSessionHasErrors('submit');

    $application->refresh();
    expect($application->status)->toBe('rascunho');
});

test('candidate can submit when concorre_vagas_pcd is true and both pcd documents are uploaded', function () {
    Storage::fake('local');
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital PcD',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
        'dados_inscricao' => [
            'step_1' => ['concorre_vagas_pcd' => true],
            'step_2' => ['concorre_vagas_sem_vinculo' => false],
            'step_3' => validApplicationStep3Payload(),
        ],
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.documents.store', $application), [
            'candidatura_document_kind' => 'pcd_declaracao',
            'arquivo' => UploadedFile::fake()->create('decl.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $this->actingAs($candidate)
        ->post(route('candidate.documents.store', $application), [
            'candidatura_document_kind' => 'pcd_laudo',
            'arquivo' => UploadedFile::fake()->create('laudo.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $this->actingAs($candidate)
        ->post(route('candidate.applications.submit', $application))
        ->assertRedirect();

    $application->refresh();
    expect($application->status)->toBe('inscrita')
        ->and(ApplicationDocument::query()->where('application_id', $application->id)->count())->toBe(2);
});
