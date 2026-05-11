<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

test('authenticated user can download edital when present', function () {
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

    $this->actingAs($candidate)
        ->get(route('selection-processes.edital.show', $process))
        ->assertOk();
});
