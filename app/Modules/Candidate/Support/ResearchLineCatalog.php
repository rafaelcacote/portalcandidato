<?php

namespace App\Modules\Candidate\Support;

class ResearchLineCatalog
{
    public const LINHA_1 = 'linha_1';

    public const LINHA_2 = 'linha_2';

    /**
     * @return array<string, string>
     */
    public static function lines(): array
    {
        return [
            self::LINHA_1 => 'Linha de Pesquisa 1 - Tecnologias Sociais e Educativas como Instrumentos para Promoção da Saúde',
            self::LINHA_2 => 'Linha de Pesquisa 2 - Tecnologias de Cuidado e de Gestão em Enfermagem e Saúde',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function advisorsByLine(): array
    {
        return [
            self::LINHA_1 => [
                'Dr. Aldalice Aguiar de Souza',
                'Dr. Altair Seabra de Farias',
                'Dra. Cleisiane Xavier Diniz',
                'Dr. Darlisom Sousa Ferreira',
                'Dra. Elizabeth Teixeira',
                'Dra. Giane Zupellari dos Santos',
                'Dra. Lise Maria Carvalho Mendes',
                'Dra. Maria de Nazaré de Souza Ribeiro',
                'Dra. Thalyta Mariany Rêgo Lopes Ueno',
            ],
            self::LINHA_2 => [
                'Dra. Amélia Nunes Sicsú',
                'Dra. Elielza Guerreiro Menezes',
                'Dra. Flávia Regina Ramos',
                'Dra. Lihsieh Marrero',
                'Dra. Kassia Janara Veras Lima',
                'Dr. Wagner Ferreira Monteiro',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function lineKeys(): array
    {
        return array_keys(self::lines());
    }

    public static function lineLabel(string $lineKey): ?string
    {
        return self::lines()[$lineKey] ?? null;
    }

    public static function isValidLine(string $lineKey): bool
    {
        return array_key_exists($lineKey, self::lines());
    }

    public static function isValidAdvisor(string $lineKey, string $advisor): bool
    {
        if (! self::isValidLine($lineKey)) {
            return false;
        }

        return in_array($advisor, self::advisorsByLine()[$lineKey], true);
    }

    /**
     * @param  array<string, mixed>|null  $step3
     * @return array{
     *     linha_pesquisa: string,
     *     linha_pesquisa_label: string,
     *     orientador: string
     * }|null
     */
    public static function summaryFromStepData(?array $step3): ?array
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
            'linha_pesquisa_label' => self::lineLabel($linhaPesquisa) ?? $linhaPesquisa,
            'orientador' => $orientador,
        ];
    }

    /**
     * @return array{
     *     lines: list<array{value: string, label: string}>,
     *     advisors: array<string, list<string>>
     * }
     */
    public static function forFrontend(): array
    {
        return [
            'lines' => collect(self::lines())
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'advisors' => self::advisorsByLine(),
        ];
    }
}
