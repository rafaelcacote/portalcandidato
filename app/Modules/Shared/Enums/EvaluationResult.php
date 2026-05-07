<?php

namespace App\Modules\Shared\Enums;

enum EvaluationResult: string
{
    case Apto = 'apto';
    case Inapto = 'inapto';
    case Classificado = 'classificado';
    case Desclassificado = 'desclassificado';
    case Suplente = 'suplente';
}
