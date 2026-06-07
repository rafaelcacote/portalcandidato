<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('admin can upload edital pdf for a selection process', function () {
    Storage::fake('local');
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Edital',
        'descricao' => 'D',
        'status' => 'rascunho',
        'tipo_programa' => 'mestrado',
    ]);

    $file = UploadedFile::fake()->create('edital-oficial.pdf', 120, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('admin.processes.edital.store', $process), [
            'edital' => $file,
        ])
        ->assertRedirect();

    $process->refresh();
    expect($process->edital_pdf_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($process->edital_pdf_path))->toBeTrue();
});

test('admin cannot upload non pdf as edital', function () {
    Storage::fake('local');
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Edital',
        'descricao' => 'D',
        'status' => 'rascunho',
        'tipo_programa' => 'mestrado',
    ]);

    $file = UploadedFile::fake()->create('notas.txt', 10, 'text/plain');

    $this->actingAs($admin)
        ->post(route('admin.processes.edital.store', $process), [
            'edital' => $file,
        ])
        ->assertSessionHasErrors('edital');

    expect($process->fresh()->edital_pdf_path)->toBeNull();
});

test('edital upload that exceeds post size limit returns validation error instead of 413 page', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Edital Grande',
        'descricao' => 'D',
        'status' => 'rascunho',
        'tipo_programa' => 'mestrado',
    ]);

    $configureUrl = route('admin.processes.show', $process);

    $this->actingAs($admin)
        ->from($configureUrl)
        ->withHeaders(['X-Inertia' => 'true'])
        ->call('POST', route('admin.processes.edital.store', $process), [], [], [], [
            'CONTENT_LENGTH' => 30 * 1024 * 1024,
        ])
        ->assertRedirect($configureUrl)
        ->assertSessionHasErrors([
            'edital' => 'O arquivo do edital não pode ultrapassar 20 MB.',
        ]);
});

test('authenticated user receives edital pdf for inline viewing when present', function () {
    Storage::fake('local');
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Download',
        'descricao' => 'D',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $path = 'process-editais/'.$process->id.'/edital.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 fake');
    $process->update(['edital_pdf_path' => $path]);

    $response = $this->actingAs($candidate)
        ->get(route('selection-processes.edital.show', $process));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->headers->get('Content-Disposition'))
        ->toContain('inline');
});

test('admin configure page exposes edital download url when edital is stored', function (): void {
    Storage::fake('local');
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Configure',
        'descricao' => 'D',
        'status' => 'rascunho',
        'tipo_programa' => 'mestrado',
    ]);

    $path = 'process-editais/'.$process->id.'/edital.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 fake');
    $process->update(['edital_pdf_path' => $path]);

    $expectedUrl = route('selection-processes.edital.show', $process);

    $this->actingAs($admin)
        ->get(route('admin.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Processes/Configure')
            ->has('selectionProcess', fn (Assert $sp) => $sp
                ->where('edital_download_url', $expectedUrl)
                ->etc()
            )
        );
});

test('admin configure page exposes null edital download url when no edital', function (): void {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Sem Edital',
        'descricao' => 'D',
        'status' => 'rascunho',
        'tipo_programa' => 'mestrado',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Processes/Configure')
            ->has('selectionProcess', fn (Assert $sp) => $sp
                ->where('edital_download_url', null)
                ->etc()
            )
        );
});
