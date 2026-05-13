<?php

namespace App\Modules\Admin\Services;

use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Modules\Shared\Enums\SelectionProcessProgramType;

class SelectionProcessTitleTemplateService
{
    /**
     * Groups shared between Mestrado and Doutorado (B through F).
     * Only the max_score differs for B and C; items are identical.
     *
     * @return array<string, array{name: string, description: string|null, order: int, items: list<array<string, mixed>>}>
     */
    private function sharedGroups(): array
    {
        return [
            'B' => [
                'name' => 'Atuação Profissional',
                'description' => 'Últimos 5 anos.',
                'order' => 2,
                'items' => [
                    [
                        'code' => 'B1',
                        'title' => 'Atividade de Enfermagem Assistencial ou de Gestão em Serviço Público ou Vinculado ao SUS',
                        'score_per_unit' => 0.40,
                        'score_unit' => 'por ano de exercício',
                        'max_quantity' => 5,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 1,
                    ],
                    [
                        'code' => 'B2',
                        'title' => 'Atividade de docência em enfermagem na graduação ou pós-graduação em Instituições Públicas ou Privadas',
                        'score_per_unit' => 0.20,
                        'score_unit' => 'por semestre',
                        'max_quantity' => 10,
                        'period_rule' => 'Últimos 5 anos (máximo 10 semestres)',
                        'order' => 2,
                    ],
                    [
                        'code' => 'B3',
                        'title' => 'Atividade de docência em enfermagem no nível médio (técnico) em Instituições Públicas ou Privadas',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por semestre',
                        'max_quantity' => 10,
                        'period_rule' => 'Últimos 5 anos (máximo 10 semestres)',
                        'order' => 3,
                    ],
                    [
                        'code' => 'B4',
                        'title' => 'Atividade de docência em cursos de pequena duração nas áreas de assistência de enfermagem, gestão ou educação em saúde, com carga horária mínima de 10 horas',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por curso',
                        'max_quantity' => 5,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 4,
                    ],
                    [
                        'code' => 'B5',
                        'title' => 'Atividade de tutoria/preceptoria de residência em enfermagem',
                        'score_per_unit' => 0.20,
                        'score_unit' => 'por semestre',
                        'max_quantity' => 10,
                        'period_rule' => 'Últimos 5 anos (máximo 10 semestres)',
                        'order' => 5,
                    ],
                    [
                        'code' => 'B6',
                        'title' => 'Participação em Comissões ou Grupo de Trabalho (GT) vinculados a serviços de saúde do SUS ou a eles vinculados',
                        'score_per_unit' => 0.15,
                        'score_unit' => 'por semestre',
                        'max_quantity' => 10,
                        'period_rule' => 'Últimos 5 anos (máximo 10 semestres)',
                        'order' => 6,
                    ],
                    [
                        'code' => 'B7',
                        'title' => 'Atividade de Gestão em Entidade de Classe na Enfermagem',
                        'score_per_unit' => 0.20,
                        'score_unit' => 'por ano',
                        'max_quantity' => 5,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 7,
                    ],
                ],
            ],
            'C' => [
                'name' => 'Produção Bibliográfica, Técnica ou Inovação',
                'description' => 'Últimos 5 anos. Obs.: a) Manuscritos submetidos a periódicos indexados que se encontram no prelo devem ser acompanhados de carta ou e-mail do editor confirmando a condição de prelo; b) trabalhos com mesmo conteúdo apresentados em mais de um evento devem ser apresentados uma única vez.',
                'order' => 3,
                'items' => [
                    [
                        'code' => 'C1',
                        'title' => 'Publicação de Artigo Científico nas áreas de Enfermagem ou de Saúde Pública, em revistas indexadas (com ISSN) – Qualis Referência (2021-2024)',
                        'score_per_unit' => 0.50,
                        'score_unit' => 'por artigo',
                        'max_quantity' => null,
                        'period_rule' => 'Últimos 5 anos',
                        'candidate_instructions' => 'A1 a A4: 0,50 por artigo; B1 a B4: 0,30 por artigo.',
                        'order' => 1,
                    ],
                    [
                        'code' => 'C2',
                        'title' => 'Autor ou Organizador de Livro (com ISBN) na área de Saúde e Enfermagem',
                        'score_per_unit' => 0.40,
                        'score_unit' => 'por título',
                        'max_quantity' => 2,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 2,
                    ],
                    [
                        'code' => 'C3',
                        'title' => 'Publicação de Capítulo de livro (com ISBN) nas áreas de Saúde e Enfermagem',
                        'score_per_unit' => 0.20,
                        'score_unit' => 'por capítulo',
                        'max_quantity' => 3,
                        'period_rule' => 'Últimos 5 anos',
                        'candidate_instructions' => 'Máximo de dois capítulos por obra e até três capítulos no total.',
                        'order' => 3,
                    ],
                    [
                        'code' => 'C4',
                        'title' => 'Trabalho completo publicado em Anais de Eventos de Saúde e Enfermagem (com ISBN)',
                        'score_per_unit' => 0.05,
                        'score_unit' => 'por trabalho',
                        'max_quantity' => 10,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 4,
                    ],
                    [
                        'code' => 'C5',
                        'title' => 'Resumo (simples ou expandido) publicado em Anais de Eventos de Saúde e Enfermagem',
                        'score_per_unit' => 0.01,
                        'score_unit' => 'por resumo',
                        'max_quantity' => 20,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 5,
                    ],
                    [
                        'code' => 'C6',
                        'title' => 'Desenvolvimento de produto, processo, técnica ou projeto tecnológico (Folder, Guia, Manual, Procedimento Operacional Padrão, cartilhas, protocolos, aplicativos ou outras tecnologias correlatas) para aplicação na Assistência de Enfermagem, no Ensino de Enfermagem ou na Gestão ou Educação em Saúde',
                        'score_per_unit' => 0.40,
                        'score_unit' => 'por produto',
                        'max_quantity' => 5,
                        'period_rule' => 'Últimos 5 anos',
                        'candidate_instructions' => 'Produtos disponíveis em repositório intelectual, auditados conforme Considerações sobre Classificação de Produção Técnica para Área de Enfermagem CAPES.',
                        'order' => 6,
                    ],
                ],
            ],
            'D' => [
                'name' => 'Participação em Eventos',
                'description' => 'Últimos 5 anos.',
                'order' => 4,
                'items' => [
                    [
                        'code' => 'D1',
                        'title' => 'Conferencista, palestrante, expositor, integrante de mesas redondas ou modalidades correlatas em eventos de Saúde e Enfermagem ou de formação acadêmica (ensino, pesquisa, extensão)',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por atividade',
                        'max_quantity' => 5,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 1,
                    ],
                    [
                        'code' => 'D2',
                        'title' => 'Membro de comissão organizadora de eventos de Saúde e Enfermagem ou de formação acadêmica (ensino, pesquisa, extensão)',
                        'score_per_unit' => 0.08,
                        'score_unit' => 'por atividade',
                        'max_quantity' => 6,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 2,
                    ],
                    [
                        'code' => 'D3',
                        'title' => 'Avaliador em evento',
                        'score_per_unit' => 0.05,
                        'score_unit' => 'por evento',
                        'max_quantity' => 10,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 3,
                    ],
                    [
                        'code' => 'D4',
                        'title' => 'Participação como ouvinte em eventos científicos',
                        'score_per_unit' => 0.01,
                        'score_unit' => 'por evento',
                        'max_quantity' => null,
                        'period_rule' => 'Últimos 5 anos',
                        'candidate_instructions' => 'Evento local e regional: 0,01 por evento; evento nacional: 0,03 por evento; evento internacional: 0,05 por evento.',
                        'order' => 4,
                    ],
                ],
            ],
            'E' => [
                'name' => 'Inserção Acadêmico-Científica',
                'description' => 'Últimos 5 anos.',
                'order' => 5,
                'items' => [
                    [
                        'code' => 'E1',
                        'title' => 'Parecerista de periódico',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por ano',
                        'max_quantity' => 3,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 1,
                    ],
                    [
                        'code' => 'E2',
                        'title' => 'Consultor ad hoc em agências de fomento',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por participação',
                        'max_quantity' => 2,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 2,
                    ],
                    [
                        'code' => 'E3',
                        'title' => 'Premiação',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por premiação',
                        'max_quantity' => 2,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 3,
                    ],
                    [
                        'code' => 'E4',
                        'title' => 'Participação em banca de avaliação (TCC, IC, Mestrado)',
                        'score_per_unit' => 0.05,
                        'score_unit' => 'por participação',
                        'max_quantity' => 4,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 4,
                    ],
                    [
                        'code' => 'E5',
                        'title' => 'Coordenação em Projetos de pesquisa com financiamento',
                        'score_per_unit' => 0.15,
                        'score_unit' => 'por projeto',
                        'max_quantity' => 2,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 5,
                    ],
                    [
                        'code' => 'E6',
                        'title' => 'Participação em Projetos de pesquisa com financiamento',
                        'score_per_unit' => 0.08,
                        'score_unit' => 'por projeto',
                        'max_quantity' => 2,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 6,
                    ],
                    [
                        'code' => 'E7',
                        'title' => 'Representação discente',
                        'score_per_unit' => 0.03,
                        'score_unit' => 'por ano',
                        'max_quantity' => 3,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 7,
                    ],
                    [
                        'code' => 'E8',
                        'title' => 'Orientação de iniciação Científica',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por orientação',
                        'max_quantity' => 3,
                        'period_rule' => 'Últimos 5 anos',
                        'order' => 8,
                    ],
                ],
            ],
            'F' => [
                'name' => 'Outros',
                'description' => 'Últimos 10 anos.',
                'order' => 6,
                'items' => [
                    [
                        'code' => 'F1',
                        'title' => 'Participação em projetos de Extensão na área de saúde ou enfermagem',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por ano',
                        'max_quantity' => 3,
                        'period_rule' => 'Últimos 10 anos',
                        'order' => 1,
                    ],
                    [
                        'code' => 'F2',
                        'title' => 'Participação na condição de Monitor no ensino de Enfermagem na graduação',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por ano',
                        'max_quantity' => 3,
                        'period_rule' => 'Últimos 10 anos',
                        'order' => 2,
                    ],
                    [
                        'code' => 'F3',
                        'title' => 'Participação como membro de Entidade Cultural, Científica e Política de Enfermagem',
                        'score_per_unit' => 0.10,
                        'score_unit' => 'por ano',
                        'max_quantity' => 3,
                        'period_rule' => 'Últimos 10 anos',
                        'order' => 3,
                    ],
                    [
                        'code' => 'F4',
                        'title' => 'Participação em Banca de Processo Seletivo',
                        'score_per_unit' => 0.05,
                        'score_unit' => 'por participação',
                        'max_quantity' => 4,
                        'period_rule' => 'Últimos 10 anos',
                        'order' => 4,
                    ],
                ],
            ],
        ];
    }

    /**
     * Group A items specific to each program type.
     *
     * @return array{mestrado: list<array<string, mixed>>, doutorado: list<array<string, mixed>>}
     */
    private function groupAItems(): array
    {
        return [
            'mestrado' => [
                [
                    'code' => 'A1',
                    'title' => 'Certificado de Especialista em Saúde Pública, com carga horária igual ou superior a 360 horas',
                    'score_per_unit' => 1.00,
                    'score_unit' => 'por título',
                    'max_quantity' => 1,
                    'period_rule' => null,
                    'order' => 1,
                ],
                [
                    'code' => 'A2',
                    'title' => 'Certificado de especialização na modalidade de Residência em Enfermagem',
                    'score_per_unit' => 1.00,
                    'score_unit' => 'por título',
                    'max_quantity' => 1,
                    'period_rule' => null,
                    'order' => 2,
                ],
                [
                    'code' => 'A3',
                    'title' => 'Certificado de Especialista em Enfermagem em outras áreas, com carga horária igual ou superior a 360 horas',
                    'score_per_unit' => 0.50,
                    'score_unit' => 'por título',
                    'max_quantity' => null,
                    'period_rule' => null,
                    'order' => 3,
                ],
                [
                    'code' => 'A4',
                    'title' => 'Curso de qualificação/aperfeiçoamento nas áreas de assistência de enfermagem, gestão ou educação em saúde e de formação docente, com carga horária mínima de 40 horas',
                    'score_per_unit' => 0.10,
                    'score_unit' => 'por curso',
                    'max_quantity' => 5,
                    'period_rule' => 'Últimos 5 anos',
                    'order' => 4,
                ],
            ],
            'doutorado' => [
                [
                    'code' => 'A1',
                    'title' => 'Diploma de Mestre em Enfermagem em Saúde Pública – Modalidade Profissional',
                    'score_per_unit' => 1.00,
                    'score_unit' => 'por título',
                    'max_quantity' => 1,
                    'period_rule' => null,
                    'order' => 1,
                ],
                [
                    'code' => 'A2',
                    'title' => 'Diploma de Mestre em outras áreas',
                    'score_per_unit' => 0.50,
                    'score_unit' => 'por título',
                    'max_quantity' => 1,
                    'period_rule' => null,
                    'order' => 2,
                ],
                [
                    'code' => 'A3',
                    'title' => 'Certificado de Especialista/Residência em Enfermagem em outras áreas, com carga horária igual ou superior a 360 horas',
                    'score_per_unit' => 0.30,
                    'score_unit' => 'por título',
                    'max_quantity' => 2,
                    'period_rule' => null,
                    'order' => 3,
                ],
                [
                    'code' => 'A4',
                    'title' => 'Curso de qualificação/aperfeiçoamento nas áreas de assistência de enfermagem, gestão ou educação em saúde e de formação docente, com carga horária mínima de 40 horas',
                    'score_per_unit' => 0.10,
                    'score_unit' => 'por curso',
                    'max_quantity' => 5,
                    'period_rule' => 'Últimos 5 anos',
                    'order' => 4,
                ],
            ],
        ];
    }

    /**
     * Max scores per group that differ between program types.
     *
     * @return array<string, array{mestrado: float, doutorado: float}>
     */
    private function groupMaxScores(): array
    {
        return [
            'A' => ['mestrado' => 2.00, 'doutorado' => 2.00],
            'B' => ['mestrado' => 4.00, 'doutorado' => 3.50],
            'C' => ['mestrado' => 2.00, 'doutorado' => 2.50],
            'D' => ['mestrado' => 0.50, 'doutorado' => 0.50],
            'E' => ['mestrado' => 1.00, 'doutorado' => 1.00],
            'F' => ['mestrado' => 0.50, 'doutorado' => 0.50],
        ];
    }

    public function syncTemplateTitleGroups(SelectionProcess $process): void
    {
        if ($process->tipo_programa === null) {
            return;
        }

        ProcessTitleGroup::query()
            ->where('selection_process_id', $process->id)
            ->where('generated_by_template', true)
            ->delete();

        $typeKey = $process->tipo_programa->value;
        $maxScores = $this->groupMaxScores();
        $groupAItems = $this->groupAItems()[$typeKey];

        $groupA = [
            'name' => 'Formação Acadêmica/Titulação',
            'description' => 'Serão considerados até dois cursos por titulação. Somente serão considerados os títulos reconhecidos pela legislação vigente.',
            'order' => 1,
            'items' => $groupAItems,
        ];

        $groups = array_merge(['A' => $groupA], $this->sharedGroups());

        foreach ($groups as $code => $group) {
            $titleGroup = $process->titleGroups()->create([
                'code' => $code,
                'name' => $group['name'],
                'description' => $group['description'] ?? null,
                'max_score' => $maxScores[$code][$typeKey],
                'order' => $group['order'],
                'is_active' => true,
                'generated_by_template' => true,
            ]);

            foreach ($group['items'] as $item) {
                $titleGroup->items()->create([
                    'code' => $item['code'],
                    'title' => $item['title'],
                    'score_per_unit' => $item['score_per_unit'],
                    'score_unit' => $item['score_unit'],
                    'max_quantity' => $item['max_quantity'] ?? null,
                    'period_rule' => $item['period_rule'] ?? null,
                    'requires_attachment' => true,
                    'accepted_formats' => ['pdf'],
                    'max_file_size_mb' => 10,
                    'candidate_instructions' => $item['candidate_instructions'] ?? null,
                    'order' => $item['order'],
                    'is_active' => true,
                ]);
            }
        }
    }

    public function shouldResyncTemplateTitleGroups(SelectionProcess $process, ?SelectionProcessProgramType $previousTipo): bool
    {
        if ($process->tipo_programa === null) {
            return false;
        }

        return $previousTipo !== $process->tipo_programa;
    }
}
