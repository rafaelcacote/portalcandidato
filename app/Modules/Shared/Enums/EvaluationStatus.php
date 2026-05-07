<?php

namespace App\Modules\Shared\Enums;

enum EvaluationStatus: string
{
    case Aguardando = 'aguardando';
    case EmAnalise = 'em_analise';
    case Concluida = 'concluida';
}
