<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreEvaluatorAssignmentRequest;
use App\Http\Requests\Modules\Admin\StoreEvaluatorRequest;
use App\Http\Requests\Modules\Admin\UpdateEvaluatorAssignmentRequest;
use App\Http\Requests\Modules\Admin\UpdateEvaluatorRequest;
use App\Models\Modules\Admin\Models\ProcessEvaluatorAssignment;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class EvaluatorController extends Controller
{
    public function index(): Response
    {
        $evaluators = User::query()
            ->role('avaliador')
            ->withCount('evaluatorAssignments')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'cpf', 'telefone', 'ativo']);

        return Inertia::render('Admin/Evaluators/Index', [
            'evaluators' => $evaluators,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Evaluators/Form');
    }

    public function store(StoreEvaluatorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['ativo'] = $data['ativo'] ?? true;

        $user = User::query()->create($data);
        $user->assignRole('avaliador');

        InertiaToast::success('Avaliador cadastrado com sucesso.');

        return redirect()->route('admin.evaluators.edit', $user);
    }

    public function edit(User $evaluator): Response
    {
        abort_unless($evaluator->hasRole('avaliador'), 404);

        $evaluator->load(['evaluatorAssignments.selectionProcess']);

        return Inertia::render('Admin/Evaluators/Form', [
            'evaluator' => [
                'id' => $evaluator->id,
                'name' => $evaluator->name,
                'email' => $evaluator->email,
                'cpf' => $evaluator->cpf,
                'telefone' => $evaluator->telefone,
                'ativo' => $evaluator->ativo,
                'assignments' => $evaluator->evaluatorAssignments->map(fn (ProcessEvaluatorAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'selection_process_id' => $assignment->selection_process_id,
                    'selection_process' => [
                        'id' => $assignment->selectionProcess->id,
                        'titulo' => $assignment->selectionProcess->titulo,
                        'status' => $assignment->selectionProcess->status,
                    ],
                    'pode_avaliar' => $assignment->pode_avaliar,
                    'pode_visualizar_resultados' => $assignment->pode_visualizar_resultados,
                    'pode_baixar_documentos' => $assignment->pode_baixar_documentos,
                ])->all(),
            ],
            'processes' => SelectionProcess::query()
                ->orderBy('titulo')
                ->get(['id', 'titulo', 'status']),
        ]);
    }

    public function update(UpdateEvaluatorRequest $request, User $evaluator): RedirectResponse
    {
        abort_unless($evaluator->hasRole('avaliador'), 404);

        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['ativo'] = $data['ativo'] ?? $evaluator->ativo;

        $evaluator->update($data);

        InertiaToast::success('Avaliador atualizado com sucesso.');

        return redirect()->route('admin.evaluators.edit', $evaluator);
    }

    public function destroy(User $evaluator): RedirectResponse
    {
        abort_unless($evaluator->hasRole('avaliador'), 404);

        $evaluator->delete();

        InertiaToast::success('Avaliador removido com sucesso.');

        return redirect()->route('admin.evaluators.index');
    }

    public function storeAssignment(StoreEvaluatorAssignmentRequest $request, User $evaluator): RedirectResponse
    {
        abort_unless($evaluator->hasRole('avaliador'), 404);

        $evaluator->evaluatorAssignments()->create([
            'selection_process_id' => $request->validated('selection_process_id'),
            'pode_avaliar' => $request->validated('pode_avaliar'),
            'pode_visualizar_resultados' => $request->validated('pode_visualizar_resultados'),
            'pode_baixar_documentos' => $request->validated('pode_baixar_documentos'),
        ]);

        InertiaToast::success('Atribuição criada com sucesso.');

        return redirect()->route('admin.evaluators.edit', $evaluator);
    }

    public function updateAssignment(
        UpdateEvaluatorAssignmentRequest $request,
        User $evaluator,
        ProcessEvaluatorAssignment $assignment
    ): RedirectResponse {
        abort_unless($evaluator->hasRole('avaliador'), 404);
        abort_unless($assignment->user_id === $evaluator->id, 404);

        $assignment->update($request->validated());

        InertiaToast::success('Permissões atualizadas com sucesso.');

        return redirect()->route('admin.evaluators.edit', $evaluator);
    }

    public function destroyAssignment(User $evaluator, ProcessEvaluatorAssignment $assignment): RedirectResponse
    {
        abort_unless($evaluator->hasRole('avaliador'), 404);
        abort_unless($assignment->user_id === $evaluator->id, 404);

        $assignment->delete();

        InertiaToast::success('Atribuição removida com sucesso.');

        return redirect()->route('admin.evaluators.edit', $evaluator);
    }
}
