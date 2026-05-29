<?php

namespace App\Modules\Shared\Enums;

enum ApplicationAppealStatus: string
{
    case Enviado = 'enviado';
    case EmAnalise = 'em_analise';
    case Deferido = 'deferido';
    case Indeferido = 'indeferido';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Enviado => 'Enviado',
            self::EmAnalise => 'Em análise',
            self::Deferido => 'Deferido',
            self::Indeferido => 'Indeferido',
        };
    }
}
