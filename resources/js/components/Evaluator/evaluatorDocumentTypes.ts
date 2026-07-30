/**
 * Application document row as serialized for the evaluator candidate review page.
 * Relation keys follow Laravel's default snake_case.
 */
export type EvaluatorTitleGroupMeta = {
    id: number;
    name: string;
    description?: string | null;
    order?: number | null;
    /** Pontuação máxima do grupo (teto para a soma dos itens do grupo). */
    max_score?: number | string | null;
};

export type EvaluatorTitleItemMeta = {
    id: number;
    title: string;
    code?: string | null;
    order?: number | null;
    /** Pontos por unidade conforme edital. */
    score_per_unit?: number | string | null;
    score_unit?: string | null;
    max_quantity?: number | null;
    title_group?: EvaluatorTitleGroupMeta | null;
};

export type EvaluatorApplicationDocument = {
    id: number;
    nome_arquivo: string;
    status: string;
    motivo_recusa?: string | null;
    process_required_document_id?: number | null;
    process_title_item_id?: number | null;
    candidatura_document_kind?: string | null;
    quantidade?: number | null;
    data_inicio?: string | null;
    data_fim?: string | null;
    required_document?: { nome: string; descricao?: string | null } | null;
    title_item?: EvaluatorTitleItemMeta | null;
};

export type EvaluatorDocumentSection = {
    key: string;
    title: string;
    description: string | null;
    kind: 'required' | 'titles' | 'special' | 'other';
    documents: EvaluatorApplicationDocument[];
};
