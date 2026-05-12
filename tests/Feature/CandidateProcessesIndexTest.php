<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guest is redirected from candidate processes index', function (): void {
    $this->get(route('candidate.processes.index'))
        ->assertRedirect(route('login'));
});

test('non-candidate cannot access candidate processes index', function (): void {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('candidate.processes.index'))
        ->assertForbidden();
});

test('candidate processes index lists active processes with edital download url when edital is linked', function (): void {
    Storage::fake('local');
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Com Edital',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $path = 'process-editais/'.$process->id.'/edital.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 fake');
    $process->update(['edital_pdf_path' => $path]);

    $expectedUrl = route('selection-processes.edital.show', $process);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Index')
            ->has('processes.data', 1)
            ->where('processes.data.0.edital_download_url', $expectedUrl)
        );
});

test('candidate processes index has null edital url when process has no edital file', function (): void {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    SelectionProcess::query()->create([
        'titulo' => 'PS Sem Edital',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Index')
            ->has('processes.data', 1)
            ->where('processes.data.0.edital_download_url', null)
        );
});
