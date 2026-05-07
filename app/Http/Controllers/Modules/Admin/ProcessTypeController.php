<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreTipoDocumentoRequest;
use App\Http\Requests\Modules\Admin\StoreTipoTituloRequest;
use App\Http\Requests\Modules\Admin\UpdateTipoDocumentoRequest;
use App\Http\Requests\Modules\Admin\UpdateTipoTituloRequest;
use App\Models\Modules\Admin\Models\TipoDocumento;
use App\Models\Modules\Admin\Models\TipoTitulo;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProcessTypeController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.support-tables.document-types.index');
    }

    public function documentTypesPage(): Response
    {
        return Inertia::render('Admin/SupportTables/DocumentTypes', [
            'tiposDocumento' => TipoDocumento::query()->latest()->get(),
        ]);
    }

    public function titleTypesPage(): Response
    {
        return Inertia::render('Admin/SupportTables/TitleTypes', [
            'tiposTitulo' => TipoTitulo::query()->latest()->get(),
        ]);
    }

    public function storeTipoDocumento(
        StoreTipoDocumentoRequest $request
    ): RedirectResponse {
        TipoDocumento::query()->create($request->validated());

        return back()->with('success', 'Tipo de documento criado com sucesso.');
    }

    public function updateTipoDocumento(
        UpdateTipoDocumentoRequest $request,
        TipoDocumento $tipoDocumento
    ): RedirectResponse {
        $tipoDocumento->update($request->validated());

        return back()->with('success', 'Tipo de documento atualizado com sucesso.');
    }

    public function destroyTipoDocumento(TipoDocumento $tipoDocumento): RedirectResponse
    {
        $tipoDocumento->delete();

        return back()->with('success', 'Tipo de documento removido com sucesso.');
    }

    public function storeTipoTitulo(StoreTipoTituloRequest $request): RedirectResponse
    {
        TipoTitulo::query()->create($request->validated());

        return back()->with('success', 'Tipo de título criado com sucesso.');
    }

    public function updateTipoTitulo(
        UpdateTipoTituloRequest $request,
        TipoTitulo $tipoTitulo
    ): RedirectResponse {
        $tipoTitulo->update($request->validated());

        return back()->with('success', 'Tipo de título atualizado com sucesso.');
    }

    public function destroyTipoTitulo(TipoTitulo $tipoTitulo): RedirectResponse
    {
        $tipoTitulo->delete();

        return back()->with('success', 'Tipo de título removido com sucesso.');
    }
}
