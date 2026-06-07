<?php

namespace App\Support;

use Illuminate\Http\Request;

final class UploadTooLargeResolver
{
    /**
     * @return array{field: string, message: string}
     */
    public static function forRequest(Request $request): array
    {
        if ($request->routeIs('admin.processes.edital.store')) {
            return [
                'field' => 'edital',
                'message' => 'O arquivo do edital não pode ultrapassar 20 MB.',
            ];
        }

        if ($request->routeIs('candidate.documents.store')) {
            return [
                'field' => 'arquivo',
                'message' => 'O arquivo enviado é muito grande. Verifique o limite permitido para este documento.',
            ];
        }

        if ($request->routeIs('profile.update')) {
            return [
                'field' => 'foto',
                'message' => 'A foto não pode ultrapassar 5 MB.',
            ];
        }

        return [
            'field' => 'upload',
            'message' => 'O arquivo enviado é muito grande. Reduza o tamanho e tente novamente.',
        ];
    }
}
