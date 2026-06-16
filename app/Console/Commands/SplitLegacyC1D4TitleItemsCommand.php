<?php

namespace App\Console\Commands;

use App\Modules\Admin\Services\SplitLegacyC1D4TitleItemsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('titles:split-c1-d4 {--process= : ID do processo seletivo (opcional; sem valor, processa todos)}')]
#[Description('Divide itens legados C1 e D4 em subitens com pontuação por faixa e remapeia comprovantes já enviados')]
class SplitLegacyC1D4TitleItemsCommand extends Command
{
    public function handle(SplitLegacyC1D4TitleItemsService $splitService): int
    {
        $processId = $this->option('process');
        $selectionProcessId = $processId !== null && $processId !== ''
            ? (int) $processId
            : null;

        if ($selectionProcessId !== null && $selectionProcessId <= 0) {
            $this->error('Informe um ID de processo válido em --process.');

            return self::FAILURE;
        }

        $stats = $splitService->splitAll($selectionProcessId);

        $this->info("Processos atualizados: {$stats['processes']}");
        $this->info("Divisões C1 → C1.1/C1.2: {$stats['c1_splits']}");
        $this->info("Divisões D4 → D4.1/D4.2/D4.3: {$stats['d4_splits']}");
        $this->info("Comprovantes remapeados: {$stats['documents_migrated']}");
        $this->newLine();
        $this->comment('Comprovantes do antigo C1 foram para C1.1 (Qualis A). Se o artigo for B1–B4, o avaliador deve ajustar para C1.2.');
        $this->comment('Comprovantes do antigo D4 foram para D4.3 (teto 0,05). O avaliador deve conferir o tipo do evento e ajustar a pontuação (0,01 / 0,03 / 0,05).');

        return self::SUCCESS;
    }
}
