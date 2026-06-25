<?php

namespace App\Modules\Candidate\Support;

class ResearchLineCatalog
{
    public const LINHA_1 = 'linha_1';

    public const LINHA_2 = 'linha_2';

    /**
     * @return array{lines: array<string, string>, advisors: array<string, list<string>>}
     */
    public static function catalogForProcess(?int $selectionProcessId): array
    {
        $byProcess = config('research_lines.by_process_id', []);

        if ($selectionProcessId !== null && isset($byProcess[$selectionProcessId])) {
            return $byProcess[$selectionProcessId];
        }

        return config('research_lines.default', [
            'lines' => [],
            'advisors' => [],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function lines(?int $selectionProcessId = null): array
    {
        return self::catalogForProcess($selectionProcessId)['lines'];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function advisorsByLine(?int $selectionProcessId = null): array
    {
        return self::catalogForProcess($selectionProcessId)['advisors'];
    }

    /**
     * @return list<string>
     */
    public static function lineKeys(?int $selectionProcessId = null): array
    {
        return array_keys(self::lines($selectionProcessId));
    }

    public static function lineLabel(string $lineKey, ?int $selectionProcessId = null): ?string
    {
        return self::lines($selectionProcessId)[$lineKey] ?? null;
    }

    public static function isValidLine(string $lineKey, ?int $selectionProcessId = null): bool
    {
        return array_key_exists($lineKey, self::lines($selectionProcessId));
    }

    public static function isValidAdvisor(string $lineKey, string $advisor, ?int $selectionProcessId = null): bool
    {
        if (! self::isValidLine($lineKey, $selectionProcessId)) {
            return false;
        }

        return in_array($advisor, self::advisorsByLine($selectionProcessId)[$lineKey], true);
    }

    /**
     * @param  array<string, mixed>|null  $step3
     * @return array{
     *     linha_pesquisa: string,
     *     linha_pesquisa_label: string,
     *     orientador: string
     * }|null
     */
    public static function summaryFromStepData(?array $step3, ?int $selectionProcessId = null): ?array
    {
        if ($step3 === null) {
            return null;
        }

        $linhaPesquisa = trim((string) ($step3['linha_pesquisa'] ?? ''));
        $orientador = trim((string) ($step3['orientador'] ?? ''));

        if ($linhaPesquisa === '' && $orientador === '') {
            return null;
        }

        return [
            'linha_pesquisa' => $linhaPesquisa,
            'linha_pesquisa_label' => self::lineLabel($linhaPesquisa, $selectionProcessId) ?? $linhaPesquisa,
            'orientador' => $orientador,
        ];
    }

    /**
     * @return array{
     *     lines: list<array{value: string, label: string}>,
     *     advisors: array<string, list<string>>
     * }
     */
    public static function forFrontend(?int $selectionProcessId = null): array
    {
        return [
            'lines' => collect(self::lines($selectionProcessId))
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'advisors' => self::advisorsByLine($selectionProcessId),
        ];
    }
}
