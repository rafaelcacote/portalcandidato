<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreSelectionProcessRequest;
use App\Http\Requests\Modules\Admin\UpdateSelectionProcessRequest;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Admin\Models\TipoDocumento;
use App\Models\Modules\Admin\Models\TipoTitulo;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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

        InertiaToast::success('Processo seletivo criado com sucesso.');

        return redirect()
            ->route('admin.processes.show', $selectionProcess);
    }

    public function show(SelectionProcess $selectionProcess): Response
    {
        $selectionProcess->load([
            'stages',
            'requiredDocuments.tipoDocumento',
            'requiredTitulos.tipoTitulo',
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

        InertiaToast::success('Processo seletivo atualizado com sucesso.');

        return redirect()
            ->route('admin.processes.show', $selectionProcess);
    }

    public function destroy(SelectionProcess $selectionProcess): RedirectResponse
    {
        $selectionProcess->delete();

        InertiaToast::success('Processo seletivo removido com sucesso.');

        return redirect()
            ->route('admin.processes.index');
    }
}
