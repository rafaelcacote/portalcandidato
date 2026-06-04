<?php

use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createCandidateForTitleUpload(): User
{
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    return $candidate;
}

function createTitleItemForApplication(Application $application, array $itemOverrides = []): ProcessTitleItem
{
    $group = ProcessTitleGroup::query()->firstOrCreate([
        'selection_process_id' => $application->selection_process_id,
        'code' => 'A',
    ], [
        'name' => 'Formação',
        'max_score' => 5.0,
        'is_active' => true,
    ]);

    return ProcessTitleItem::query()->create(array_merge([
        'process_title_group_id' => $group->id,
        'code' => 'A.1',
        'title' => 'Especialização',
        'score_per_unit' => 1.0,
        'score_unit' => 'por título',
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'is_active' => true,
    ], $itemOverrides));
}

test('candidate can upload a title proof document for a process title item', function (): void {
    Storage::fake('local');
    $candidate = createCandidateForTitleUpload();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital com títulos',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $item = createTitleItemForApplication($application);

    $file = UploadedFile::fake()->create('comprovante.pdf', 200, 'application/pdf');

    $this->actingAs($candidate)
        ->post(route('candidate.documents.store', $application), [
            'process_title_item_id' => $item->id,
            'arquivo' => $file,
        ])
        ->assertRedirect();

    $documents = ApplicationDocument::query()->get();

    expect($documents)->toHaveCount(1)
        ->and($documents->first()->process_title_item_id)->toBe($item->id)
        ->and($documents->first()->candidatura_document_kind)->toBeNull()
        ->and($documents->first()->process_required_document_id)->toBeNull()
        ->and($documents->first()->status)->toBe('enviado')
        ->and($documents->first()->quantidade)->toBe(1);
});

test('candidate can upload multiple proofs for the same title item up to max_quantity', function (): void {
    Storage::fake('local');
    $candidate = createCandidateForTitleUpload();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital com títulos',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);
    $item = createTitleItemForApplication($application, ['max_quantity' => 3]);

    foreach (['a.pdf', 'b.pdf', 'c.pdf'] as $name) {
        $this->actingAs($candidate)
            ->post(route('candidate.documents.store', $application), [
                'process_title_item_id' => $item->id,
                'arquivo' => UploadedFile::fake()->create($name, 100, 'application/pdf'),
            ])
            ->assertRedirect();
    }

    $documents = ApplicationDocument::query()
        ->where('process_title_item_id', $item->id)
        ->orderBy('id')
        ->get();

    expect($documents)->toHaveCount(3)
        ->and($documents->pluck('nome_arquivo')->all())->toEqual(['a.pdf', 'b.pdf', 'c.pdf']);
});

test('candidate cannot upload more proofs than max_quantity for a title item', function (): void {
    Storage::fake('local');
    $candidate = createCandidateForTitleUpload();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital com títulos',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);
    $item = createTitleItemForApplication($application, ['max_quantity' => 2]);

    foreach (['1.pdf', '2.pdf'] as $name) {
        $this->actingAs($candidate)
            ->post(route('candidate.documents.store', $application), [
                'process_title_item_id' => $item->id,
                'arquivo' => UploadedFile::fake()->create($name, 100, 'application/pdf'),
            ])
            ->assertRedirect();
    }

    $this->actingAs($candidate)
        ->post(route('candidate.documents.store', $application), [
            'process_title_item_id' => $item->id,
            'arquivo' => UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('process_title_item_id');

    expect(
        ApplicationDocument::query()->where('process_title_item_id', $item->id)->count(),
    )->toBe(2);
});

test('candidate can upload unlimited proofs when max_quantity is null', function (): void {
    Storage::fake('local');
    $candidate = createCandidateForTitleUpload();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital com títulos',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);
    $item = createTitleItemForApplication($application, ['max_quantity' => null]);

    foreach (range(1, 6) as $i) {
        $this->actingAs($candidate)
            ->post(route('candidate.documents.store', $application), [
                'process_title_item_id' => $item->id,
                'arquivo' => UploadedFile::fake()->create("doc{$i}.pdf", 100, 'application/pdf'),
            ])
            ->assertRedirect();
    }

    expect(
        ApplicationDocument::query()->where('process_title_item_id', $item->id)->count(),
    )->toBe(6);
});

test('candidate cannot upload title proof for an item from another selection process', function (): void {
    Storage::fake('local');
    $candidate = createCandidateForTitleUpload();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital com títulos',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $otherProcess = SelectionProcess::query()->create([
        'titulo' => 'Outro edital',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);
    $otherGroup = ProcessTitleGroup::query()->create([
        'selection_process_id' => $otherProcess->id,
        'code' => 'X',
        'name' => 'Outro',
        'max_score' => 1.0,
        'is_active' => true,
    ]);
    $foreignItem = ProcessTitleItem::query()->create([
        'process_title_group_id' => $otherGroup->id,
        'code' => 'X.1',
        'title' => 'Item de outro edital',
        'score_per_unit' => 1.0,
        'score_unit' => 'por título',
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.documents.store', $application), [
            'process_title_item_id' => $foreignItem->id,
            'arquivo' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('process_title_item_id');

    expect(ApplicationDocument::query()->count())->toBe(0);
});

test('candidate cannot upload a file in a format not accepted by the title item', function (): void {
    Storage::fake('local');
    $candidate = createCandidateForTitleUpload();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital com títulos',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);
    $item = createTitleItemForApplication($application, [
        'accepted_formats' => ['pdf'],
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.documents.store', $application), [
            'process_title_item_id' => $item->id,
            'arquivo' => UploadedFile::fake()->image('foto.jpg'),
        ])
        ->assertSessionHasErrors('arquivo');

    expect(ApplicationDocument::query()->count())->toBe(0);
});

test('candidate can delete one of their uploaded title proofs', function (): void {
    Storage::fake('local');
    $candidate = createCandidateForTitleUpload();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital com títulos',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);
    $item = createTitleItemForApplication($application, ['max_quantity' => 3]);

    foreach (['a.pdf', 'b.pdf'] as $name) {
        $this->actingAs($candidate)
            ->post(route('candidate.documents.store', $application), [
                'process_title_item_id' => $item->id,
                'arquivo' => UploadedFile::fake()->create($name, 100, 'application/pdf'),
            ])
            ->assertRedirect();
    }

    $first = ApplicationDocument::query()
        ->where('process_title_item_id', $item->id)
        ->orderBy('id')
        ->first();

    $this->actingAs($candidate)
        ->delete(route('candidate.documents.destroy', [
            'application' => $application,
            'document' => $first,
        ]))
        ->assertRedirect();

    expect(
        ApplicationDocument::query()->where('process_title_item_id', $item->id)->count(),
    )->toBe(1)
        ->and(ApplicationDocument::query()->find($first->id))->toBeNull();
});

test('candidate cannot delete a document from another candidate', function (): void {
    Storage::fake('local');
    $owner = createCandidateForTitleUpload();
    $intruder = User::factory()->create(['email_verified_at' => now()]);
    $intruder->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $owner->id,
        'status' => 'rascunho',
    ]);
    $item = createTitleItemForApplication($application);

    $this->actingAs($owner)
        ->post(route('candidate.documents.store', $application), [
            'process_title_item_id' => $item->id,
            'arquivo' => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $document = ApplicationDocument::query()->firstOrFail();

    $this->actingAs($intruder)
        ->delete(route('candidate.documents.destroy', [
            'application' => $application,
            'document' => $document,
        ]))
        ->assertForbidden();

    expect(ApplicationDocument::query()->find($document->id))->not->toBeNull();
});

test('finalized application blocks deleting title proofs', function (): void {
    Storage::fake('local');
    $candidate = createCandidateForTitleUpload();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);
    $item = createTitleItemForApplication($application);

    $this->actingAs($candidate)
        ->post(route('candidate.documents.store', $application), [
            'process_title_item_id' => $item->id,
            'arquivo' => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $document = ApplicationDocument::query()->firstOrFail();

    $application->update([
        'status' => 'inscrita',
        'finalizada_em' => now(),
    ]);

    $this->actingAs($candidate)
        ->delete(route('candidate.documents.destroy', [
            'application' => $application,
            'document' => $document,
        ]))
        ->assertStatus(422);

    expect(ApplicationDocument::query()->find($document->id))->not->toBeNull();
});

test('candidate application page exposes linked document titles on uploaded documents', function (): void {
    Storage::fake('local');
    $candidate = createCandidateForTitleUpload();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital com documentos',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $required = ProcessRequiredDocument::query()->create([
        'selection_process_id' => $process->id,
        'nome' => 'RG (frente e verso)',
        'obrigatorio' => true,
    ]);

    ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_required_document_id' => $required->id,
        'caminho' => 'private/documents/test/rg.pdf',
        'nome_arquivo' => 'rg.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $item = createTitleItemForApplication($application, [
        'code' => 'A.1',
        'title' => 'Especialização em Saúde',
    ]);

    ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_title_item_id' => $item->id,
        'caminho' => 'private/documents/test/comprovante.pdf',
        'nome_arquivo' => 'comprovante.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.applications.show', $application))
        ->assertSuccessful()
        ->assertInertia(function ($page): void {
            $page->component('Candidate/Applications/Show')
                ->has('application.documents', 2);

            $documents = collect($page->toArray()['props']['application']['documents']);
            $requiredDoc = $documents->firstWhere('nome_arquivo', 'rg.pdf');
            $titleDoc = $documents->firstWhere('nome_arquivo', 'comprovante.pdf');

            expect($requiredDoc['required_document']['nome'])->toBe('RG (frente e verso)')
                ->and($titleDoc['title_item']['title'])->toBe('Especialização em Saúde')
                ->and($titleDoc['title_item']['code'])->toBe('A.1');
        });
});
