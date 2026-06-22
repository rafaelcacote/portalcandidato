<?php

namespace App\Modules\Candidate\Support;

class EmploymentRelationshipCatalog
{
    /**
     * @param  array<string, mixed>|null  $step2
     * @return array{
     *     concorre_vagas_sem_vinculo: bool,
     *     resposta_label: string
     * }|null
     */
    public static function summaryFromStepData(?array $step2): ?array
    {
        if ($step2 === null || ! array_key_exists('concorre_vagas_sem_vinculo', $step2)) {
            return null;
        }

        $concorre = (bool) $step2['concorre_vagas_sem_vinculo'];

        return [
            'concorre_vagas_sem_vinculo' => $concorre,
            'resposta_label' => $concorre ? 'Sim' : 'Não',
        ];
    }
}
