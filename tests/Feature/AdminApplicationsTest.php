<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guest is redirected from admin applications index', function (): void {
    $this->get(route('admin.applications.index'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access admin applications index', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('admin.applications.index'))
        ->assertForbidden();
});

test('admin sees applications listing', function (): void {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('candidato', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $candidate = User::factory()->create([
        'name' => 'Maria Silva',
        'email' => 'maria@example.com',
        'email_verified_at' => now(),
    ]);
    $candidate->assignRole('candidato');

    Storage::fake('public');
    $photoPath = UploadedFile::fake()
        ->image('foto.jpg')
        ->store('candidate-photos/'.$candidate->id, 'public');
    $candidate->forceFill(['foto_path' => $photoPath])->save();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Mestrado 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Inscrita->value,
        'numero_protocolo' => '2026-0001',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.applications.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Applications/Index')
            ->has('applications.data', 1)
            ->where('applications.data.0.status', ApplicationStatus::Inscrita->value)
            ->where('applications.data.0.numero_protocolo', '2026-0001')
            ->where('applications.data.0.candidate.name', 'Maria Silva')
            ->where('applications.data.0.candidate.email', 'maria@example.com')
            ->where('applications.data.0.candidate.photo_url', '/storage/'.$photoPath)
            ->where('applications.data.0.selection_process.titulo', 'Processo Mestrado 2026')
        );
});

test('admin can view candidate photo from applications listing', function (): void {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('candidato', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    Storage::fake('public');
    $photoPath = UploadedFile::fake()
        ->image('foto.jpg')
        ->store('candidate-photos/'.$candidate->id, 'public');
    $candidate->forceFill(['foto_path' => $photoPath])->save();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Inscrita->value,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.applications.photo', $application))
        ->assertSuccessful();
});
