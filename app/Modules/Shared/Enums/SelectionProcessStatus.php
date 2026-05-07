<?php

namespace App\Modules\Shared\Enums;

enum SelectionProcessStatus: string
{
    case Rascunho = 'rascunho';
    case Ativo = 'ativo';
    case Encerrado = 'encerrado';
}
