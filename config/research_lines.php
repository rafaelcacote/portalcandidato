<?php

/**
 * Linhas de pesquisa e orientadores por processo seletivo.
 *
 * Chave: selection_processes.id (produção: 1 = Doutorado 053/2026, 2 = Mestrado 054/2026).
 * Processos sem entrada aqui usam o catálogo "default".
 */
return [
    'by_process_id' => [
        // EDITAL Nº 053/2026 - Doutorado Profissional em Enfermagem em Saúde Pública
        1 => [
            'lines' => [
                'linha_1' => 'Linha de Pesquisa 1 - Tecnologias Sociais e Educativas como Instrumentos para Promoção da Saúde',
                'linha_2' => 'Linha de Pesquisa 2 - Tecnologias de Cuidado e de Gestão em Enfermagem e Saúde',
            ],
            'advisors' => [
                'linha_1' => [
                    'Dra. Aldalice Aguiar de Souza',
                    'Dr. Altair Seabra de Farias',
                    'Dra. Cleisiane Xavier Diniz',
                    'Dr. Darlisom Sousa Ferreira',
                    'Dra. Elizabeth Teixeira',
                    'Dra. Giane Zupellari dos Santos',
                    'Dra. Maria de Nazaré de Souza Ribeiro',
                ],
                'linha_2' => [
                    'Dra. Amélia Nunes Sicsú',
                    'Dra. Elielza Guerreiro Menezes',
                    'Dra. Flávia Regina Ramos',
                    'Dra. Jacqueline de Almeida Gonçalves Sachett',
                    'Dra. Lihsieh Marrero',
                    'Dr. Wagner Ferreira Monteiro',
                ],
            ],
        ],

        // EDITAL Nº 054/2026 - Mestrado Profissional em Enfermagem em Saúde Pública
        2 => [
            'lines' => [
                'linha_1' => 'Linha de Pesquisa 1 - Tecnologias Sociais e Educativas como Instrumentos para Promoção da Saúde',
                'linha_2' => 'Linha de Pesquisa 2 - Tecnologias de Cuidado e de Gestão em Enfermagem e Saúde',
            ],
            'advisors' => [
                'linha_1' => [
                    'Dra. Aldalice Aguiar de Souza',
                    'Dr. Altair Seabra de Farias',
                    'Dra. Cleisiane Xavier Diniz',
                    'Dr. Darlisom Sousa Ferreira',
                    'Dra. Elizabeth Teixeira',
                    'Dra. Giane Zupellari dos Santos',
                    'Dra. Maria de Nazaré de Souza Ribeiro',
                    'Dra. Thalyta Mariany Rêgo Lopes Ueno',
                ],
                'linha_2' => [
                    'Dra. Amélia Nunes Sicsú',
                    'Dra. Elielza Guerreiro Menezes',
                    'Dra. Flávia Regina Ramos',
                    'Dra. Lihsieh Marrero',
                    'Dra. Kassia Janara Veras Lima',
                    'Dr. Wagner Ferreira Monteiro',
                ],
            ],
        ],
    ],

    'default' => [
        'lines' => [
            'linha_1' => 'Linha de Pesquisa 1 - Tecnologias Sociais e Educativas como Instrumentos para Promoção da Saúde',
            'linha_2' => 'Linha de Pesquisa 2 - Tecnologias de Cuidado e de Gestão em Enfermagem e Saúde',
        ],
        'advisors' => [
            'linha_1' => [
                'Dra. Aldalice Aguiar de Souza',
                'Dr. Altair Seabra de Farias',
                'Dra. Cleisiane Xavier Diniz',
                'Dr. Darlisom Sousa Ferreira',
                'Dra. Elizabeth Teixeira',
                'Dra. Giane Zupellari dos Santos',
                'Dra. Lise Maria Carvalho Mendes',
                'Dra. Maria de Nazaré de Souza Ribeiro',
                'Dra. Thalyta Mariany Rêgo Lopes Ueno',
            ],
            'linha_2' => [
                'Dra. Amélia Nunes Sicsú',
                'Dra. Elielza Guerreiro Menezes',
                'Dra. Flávia Regina Ramos',
                'Dra. Jacqueline de Almeida Gonçalves Sachett',
                'Dra. Lihsieh Marrero',
                'Dra. Kassia Janara Veras Lima',
                'Dr. Wagner Ferreira Monteiro',
            ],
        ],
    ],
];
