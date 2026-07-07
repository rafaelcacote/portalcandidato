<?php

use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('documents index does not expose upload for closed process applications', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Encerrado',
        'descricao' => 'D',
        'status' => 'encerrado',
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.documents.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Documents/Index')
            ->where('has_uploadable_applications', false)
            ->has('applications', 1)
            ->where('applications.0.can_upload_documents', false)
        );
});

test('candidate cannot upload documents for application on closed process', function (): void {
    Storage::fake('local');
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Encerrado',
        'descricao' => 'D',
        'status' => 'encerrado',
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $required = ProcessRequiredDocument::query()->create([
        'selection_process_id' => $process->id,
        'nome' => 'RG',
        'obrigatorio' => true,
    ]);

    $file = UploadedFile::fake()->create('rg.pdf', 200, 'application/pdf');

    $this->actingAs($candidate)
        ->post(route('candidate.documents.store', $application), [
            'process_required_document_id' => $required->id,
            'arquivo' => $file,
        ])
        ->assertForbidden();
});
