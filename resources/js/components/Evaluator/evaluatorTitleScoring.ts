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
