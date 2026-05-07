<?php

namespace App\Modules\Shared\Enums;

enum ApplicationStatus: string
{
    case Rascunho = 'rascunho';
    case Inscrita = 'inscrita';
    case EmAnalise = 'em_analise';
    case Pendencia = 'pendencia';
    case Aprovada = 'aprovada';
    case Reprovada = 'reprovada';
    case Cancelada = 'cancelada';
}
