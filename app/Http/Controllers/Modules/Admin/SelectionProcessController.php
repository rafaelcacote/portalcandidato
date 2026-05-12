<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreSelectionProcessRequest;
use App\Http\Requests\Modules\Admin\UpdateSelectionProcessRequest;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Admin\Models\TipoDocumento;
use App\Models\Modules\Admin\Models\TipoTitulo;
use App\Modules\Admin\Services\SelectionProcessDocumentTemplateService;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SelectionProcessController extends Controller
{
    public function __construct(
        private readonly SelectionProcessDocumentTemplateService $documentTemplateService,
    ) {}

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
        $this->documentTemplateService->syncTemplateDocuments($selectionProcess);

        InertiaToast::success('Processo seletivo criado com sucesso.');

        return redirect()
            ->route('admin.processes.show', $selectionProcess);
    }

    public function show(SelectionProcess $selectionProcess): Response
    {
        $selectionProcess->load([
            'stages',
            'requiredDocuments.tipoDocumento',
            'titleGroups.items',
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
        $previousTipo = $selectionProcess->tipo_programa;
        $selectionProcess->update($request->validated());
        $selectionProcess->refresh();

        if ($this->documentTemplateService->shouldResyncTemplateDocuments($selectionProcess, $previousTipo)) {
            $this->documentTemplateService->syncTemplateDocuments($selectionProcess);
        }

        InertiaToast::success('Processo seletivo atualizado com sucesso.');

        return redirect()
            ->route('admin.processes.show', $selectionProcess);
    }

    public function destroy(SelectionProcess $selectionProcess): RedirectResponse
    {
        if ($selectionProcess->edital_pdf_path !== null) {
            Storage::disk('local')->delete($selectionProcess->edital_pdf_path);
        }

        $selectionProcess->delete();

        InertiaToast::success('Processo seletivo removido com sucesso.');

        return redirect()
            ->route('admin.processes.index');
    }
}
