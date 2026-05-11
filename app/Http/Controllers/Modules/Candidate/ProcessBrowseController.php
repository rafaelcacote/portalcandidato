<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcessBrowseController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SelectionProcess::query()->where('status', 'ativo');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search): void {
                $q->where('titulo', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%")
                    ->orWhere('orgao', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        if ($orgao = $request->string('orgao')->trim()->value()) {
            $query->where('orgao', 'like', "%{$orgao}%");
        }

        if ($area = $request->string('area')->trim()->value()) {
            $query->where('area', 'like', "%{$area}%");
        }

        if ($periodoInicio = $request->string('periodo_inicio')->trim()->value()) {
            $query->whereDate('inscricao_inicio_em', '>=', $periodoInicio);
        }

        if ($periodoFim = $request->string('periodo_fim')->trim()->value()) {
            $query->whereDate('inscricao_fim_em', '<=', $periodoFim);
        }

        return Inertia::render('Candidate/Processes/Index', [
            'processes' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'orgao', 'area', 'periodo_inicio', 'periodo_fim']),
        ]);
    }

    public function show(SelectionProcess $selectionProcess, Request $request): Response
    {
        $selectionProcess->load(['stages', 'requiredDocuments', 'criteria']);

        $alreadyApplied = Application::query()
            ->where('selection_process_id', $selectionProcess->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        $selectionProcess->setAttribute(
            'edital_download_url',
            $selectionProcess->edital_pdf_path !== null
                ? route('selection-processes.edital.show', $selectionProcess)
                : null,
        );

        return Inertia::render('Candidate/Processes/Show', [
            'selectionProcess' => $selectionProcess,
            'alreadyApplied' => $alreadyApplied,
        ]);
    }
}
