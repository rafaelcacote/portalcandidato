<?php

namespace App\Modules\Shared\Enums;

enum SelectionProcessProgramType: string
{
    case Mestrado = 'mestrado';
    case Doutorado = 'doutorado';

    public function label(): string
    {
        return match ($this) {
            self::Mestrado => 'Mestrado',
            self::Doutorado => 'Doutorado',
        };
    }
}
