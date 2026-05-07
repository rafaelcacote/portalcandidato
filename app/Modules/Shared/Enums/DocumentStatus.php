<?php

namespace App\Modules\Shared\Enums;

enum DocumentStatus: string
{
    case Pendente = 'pendente';
    case Enviado = 'enviado';
    case Aprovado = 'aprovado';
    case Recusado = 'recusado';
}
