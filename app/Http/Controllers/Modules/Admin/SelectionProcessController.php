<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreSelectionProcessRequest;
use App\Http\Requests\Modules\Admin\UpdateSelectionProcessRequest;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Admin\Models\TipoDocumento;
use App\Models\Modules\Admin\Models\TipoTitulo;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SelectionProcessController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Processes/Index', [
            'processes' => SelectionProcess::query()->latest()->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Processes/Form');
    }

    public function store(StoreSelectionProcessRequest $request): RedirectResponse
    {
        $selectionProcess = SelectionProcess::query()->create($request->validated());

        return redirect()
            ->route('admin.processes.show', $selectionProcess)
            ->with('success', 'Processo seletivo criado com sucesso.');
    }

    public function show(SelectionProcess $selectionProcess): Response
    {
        $selectionProcess->load([
            'stages',
            'requiredDocuments.tipoDocumento',
            'requiredDocuments.tipoTitulo',
            'criteria',
            'evaluatorAssignments',
            'applicationFields',
        ]);

        return Inertia::render('Admin/Processes/Configure', [
            'selectionProcess' => $selectionProcess,
            'tiposDocumento' => TipoDocumento::query()
                ->where('status', true)
                ->orderBy('descricao')
                ->get(['id', 'descricao']),
            'tiposTitulo' => TipoTitulo::query()
                ->where('status', true)
                ->orderBy('descricao')
                ->get(['id', 'descricao', 'calculo']),
        ]);
    }

    public function edit(SelectionProcess $selectionProcess): Response
    {
        return Inertia::render('Admin/Processes/Form', [
            'selectionProcess' => $selectionProcess,
        ]);
    }

    public function update(UpdateSelectionProcessRequest $request, SelectionProcess $selectionProcess): RedirectResponse
    {
        $selectionProcess->update($request->validated());

        return redirect()
            ->route('admin.processes.show', $selectionProcess)
            ->with('success', 'Processo seletivo atualizado com sucesso.');
    }

    public function destroy(SelectionProcess $selectionProcess): RedirectResponse
    {
        $selectionProcess->delete();

        return redirect()
            ->route('admin.processes.index')
            ->with('success', 'Processo seletivo removido com sucesso.');
    }
}
