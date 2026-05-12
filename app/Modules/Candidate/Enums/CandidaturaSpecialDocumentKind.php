<?php

namespace App\Modules\Candidate\Enums;

enum CandidaturaSpecialDocumentKind: string
{
    case PcdDeclaracao = 'pcd_declaracao';
    case PcdLaudo = 'pcd_laudo';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
