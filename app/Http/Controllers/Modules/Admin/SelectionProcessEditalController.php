<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreSelectionProcessEditalRequest;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class SelectionProcessEditalController extends Controller
{
    public function store(
        StoreSelectionProcessEditalRequest $request,
        SelectionProcess $selectionProcess
    ): RedirectResponse {
        $file = $request->file('edital');
        if ($file === null) {
            return back();
        }

        if ($selectionProcess->edital_pdf_path !== null) {
            Storage::disk('local')->delete($selectionProcess->edital_pdf_path);
        }

        $directory = "process-editais/{$selectionProcess->id}";
        $path = $file->store($directory, 'local');

        $selectionProcess->update([
            'edital_pdf_path' => $path,
        ]);

        InertiaToast::success('Edital em PDF atualizado com sucesso.');

        return back();
    }

    public function destroy(SelectionProcess $selectionProcess): RedirectResponse
    {
        if ($selectionProcess->edital_pdf_path !== null) {
            Storage::disk('local')->delete($selectionProcess->edital_pdf_path);
            $selectionProcess->update([
                'edital_pdf_path' => null,
            ]);
        }

        InertiaToast::success('Edital removido do processo.');

        return back();
    }
}
