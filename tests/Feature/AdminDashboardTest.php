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

test('guest is redirected from admin dashboard', function (): void {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('admin sees dashboard inertia props', function (): void {
    Role::findOrCreate('admin', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('stats', fn (Assert $stats) => $stats
                ->where('processes_total', 0)
                ->where('processes_rascunho', 0)
                ->where('processes_ativo', 0)
                ->where('processes_encerrado', 0)
                ->where('applications_total', 0)
                ->where('applications_rascunho', 0)
                ->where('applications_em_fluxo', 0)
                ->where('applications_aprovada', 0)
                ->where('evaluators_total', 0)
                ->where('conversion_percent', 0)
            )
            ->has('applications_trend', 30)
            ->where('highlight_process', null)
            ->has('recent_processes', 0)
            ->has('recent_applications', 0));
});

test('admin dashboard aggregates counts', function (): void {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('avaliador', 'web');
    Role::findOrCreate('candidato', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $processA = SelectionProcess::query()->create([
        'titulo' => 'Processo A',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    SelectionProcess::query()->create([
        'titulo' => 'Processo B',
        'descricao' => 'D',
        'status' => 'rascunho',
    ]);

    $processC = SelectionProcess::query()->create([
        'titulo' => 'Processo C',
        'descricao' => 'D',
        'status' => 'encerrado',
    ]);

    $processD = SelectionProcess::query()->create([
        'titulo' => 'Processo D',
        'descricao' => 'D',
        'status' => 'encerrado',
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $processC->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $processD->id,
        'status' => ApplicationStatus::EmAnalise->value,
    ]);

    Storage::fake('public');
    $photoPath = UploadedFile::fake()
        ->image('foto.jpg')
        ->store('candidate-photos/'.$candidate->id, 'public');
    $candidate->forceFill(['foto_path' => $photoPath])->save();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('stats.processes_total', 4)
            ->where('stats.processes_ativo', 1)
            ->where('stats.processes_rascunho', 1)
            ->where('stats.processes_encerrado', 2)
            ->where('stats.applications_total', 2)
            ->where('stats.applications_rascunho', 1)
            ->where('stats.applications_em_fluxo', 1)
            ->where('stats.applications_aprovada', 0)
            ->where('stats.conversion_percent', 0)
            ->where('stats.evaluators_total', 1)
            ->has('applications_trend', 30)
            ->has('highlight_process', fn (Assert $h) => $h
                ->where('id', $processA->id)
                ->where('titulo', 'Processo A')
                ->hasAll(['inscricao_inicio_em', 'inscricao_fim_em'])
            )
            ->has('recent_processes', 4)
            ->has('recent_applications', 2)
            ->where('recent_applications.0.candidate_photo_url', '/storage/'.$photoPath)
        );
});
