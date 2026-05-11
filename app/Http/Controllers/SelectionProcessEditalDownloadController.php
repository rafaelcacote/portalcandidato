<?php

namespace App\Http\Controllers;

use App\Models\Modules\Admin\Models\SelectionProcess;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SelectionProcessEditalDownloadController extends Controller
{
    public function show(SelectionProcess $selectionProcess): StreamedResponse|Response
    {
        if ($selectionProcess->edital_pdf_path === null) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($selectionProcess->edital_pdf_path)) {
            abort(404);
        }

        $filename = 'edital-'.preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $selectionProcess->titulo).'.pdf';

        return Storage::disk('local')->download(
            $selectionProcess->edital_pdf_path,
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
