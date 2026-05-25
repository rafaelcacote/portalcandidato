import type { EvaluatorApplicationDocument } from '@/components/Evaluator/evaluatorDocumentTypes';

/**
 * Pontuação máxima permitida para uma linha de documento de titulação,
 * alinhada à regra do backend (score_per_unit × quantidade, respeitando max_quantity).
 */
export function maxPointsForTitleDocumentRow(doc: EvaluatorApplicationDocument): number | null {
    const ti = doc.title_item;
    if (ti == null || ti.score_per_unit == null || ti.score_per_unit === '') {
        return null;
    }
    const perUnit = Number(ti.score_per_unit);
    if (!Number.isFinite(perUnit) || perUnit < 0) {
        return null;
    }
    let qty = Math.max(1, Number(doc.quantidade ?? 1));
    if (ti.max_quantity != null) {
        qty = Math.min(qty, Number(ti.max_quantity));
    }
    return Math.round(perUnit * qty * 100) / 100;
}

/**
 * Pontos efetivos ao aprovar, respeitando o teto do grupo (mesma regra do backend).
 */
export function resolvePointsForApprovedTitleDocument(
    doc: EvaluatorApplicationDocument,
    documentScores: Array<{ application_document_id: number; pontuacao: number }>,
    allDocuments: EvaluatorApplicationDocument[],
): number {
    const rowMax = maxPointsForTitleDocumentRow(doc);
    if (rowMax == null || rowMax <= 0) {
        return 0;
    }

    const groupId = doc.title_item?.title_group?.id;
    const groupMaxRaw = doc.title_item?.title_group?.max_score;
    if (groupId == null || groupMaxRaw == null || groupMaxRaw === '') {
        return rowMax;
    }

    const groupMax = Number(groupMaxRaw);
    const groupDocIds = new Set(
        allDocuments
            .filter((d) => d.title_item?.title_group?.id === groupId)
            .map((d) => d.id),
    );

    const otherSum = documentScores
        .filter((s) => groupDocIds.has(s.application_document_id) && s.application_document_id !== doc.id)
        .reduce((sum, s) => sum + Number(s.pontuacao ?? 0), 0);

    const remaining = Math.max(0, groupMax - otherSum);
    return Math.round(Math.min(rowMax, remaining) * 100) / 100;
}
