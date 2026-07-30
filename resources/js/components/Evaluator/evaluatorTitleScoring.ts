import type { EvaluatorApplicationDocument } from '@/components/Evaluator/evaluatorDocumentTypes';

/** Códigos do edital cuja quantidade deriva de data início/fim. */
export const PERIOD_DATE_CODES = [
    'B1',
    'B2',
    'B3',
    'B5',
    'B6',
    'B7',
] as const;

export function normalizeTitleCode(code?: string | null): string {
    return (code ?? '').replace(/[.\s]/g, '').toUpperCase();
}

export function usesPeriodDates(code?: string | null): boolean {
    return (PERIOD_DATE_CODES as readonly string[]).includes(
        normalizeTitleCode(code),
    );
}

/**
 * Rótulo amigável da unidade de quantidade conforme o edital (anos, semestres, etc.).
 */
export function quantityLabelForScoreUnit(
    scoreUnit: string | null | undefined,
): string {
    const unit = (scoreUnit ?? '').toLowerCase();

    if (unit.includes('semestre')) {
        return 'Semestres';
    }

    if (unit.includes('ano')) {
        return 'Anos';
    }

    return 'Quantidade';
}

export function unitIsSemester(scoreUnit?: string | null): boolean {
    return (scoreUnit ?? '').toLowerCase().includes('semestre');
}

function windowStartFor(windowEnd: Date, windowYears: number): Date {
    const windowStart = new Date(windowEnd);
    windowStart.setFullYear(windowStart.getFullYear() - windowYears);

    return windowStart;
}

/**
 * Indica se parte do período informado está fora da janela dos últimos N
 * anos do edital (mesma regra do backend), para avisar o avaliador na tela.
 */
export function periodExceedsWindow(
    dataInicio: string,
    dataFim: string,
    windowEndIso: string,
    windowYears = 5,
): boolean {
    const start = parseDateOnly(dataInicio);
    const end = parseDateOnly(dataFim);
    const windowEnd = parseDateOnly(windowEndIso.slice(0, 10));

    if (!start || !end || !windowEnd) {
        return false;
    }

    const windowStart = windowStartFor(windowEnd, windowYears);

    return start < windowStart || end > windowEnd;
}

/**
 * Quantidade inteira a partir das datas (mesma regra do backend: só inteiros,
 * cortando o que estiver fora da janela do edital).
 */
export function quantityFromPeriodDates(
    dataInicio: string,
    dataFim: string,
    asSemesters: boolean,
    windowEndIso: string,
    windowYears = 5,
): number {
    const start = parseDateOnly(dataInicio);
    const end = parseDateOnly(dataFim);
    const windowEnd = parseDateOnly(windowEndIso.slice(0, 10));

    if (!start || !end || !windowEnd || end < start) {
        return 0;
    }

    const windowStart = windowStartFor(windowEnd, windowYears);

    const clippedStart = start > windowStart ? start : windowStart;
    const clippedEnd = end < windowEnd ? end : windowEnd;

    if (clippedStart >= clippedEnd) {
        return 0;
    }

    const totalMonths = monthsBetween(clippedStart, clippedEnd);

    if (asSemesters) {
        return Math.floor(totalMonths / 6);
    }

    return Math.floor(totalMonths / 12);
}

function parseDateOnly(value: string): Date | null {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value);

    if (!match) {
        return null;
    }

    return new Date(
        Number(match[1]),
        Number(match[2]) - 1,
        Number(match[3]),
        0,
        0,
        0,
        0,
    );
}

/** Meses completos entre duas datas (calendário), sem arredondar para cima. */
function monthsBetween(start: Date, end: Date): number {
    let years = end.getFullYear() - start.getFullYear();
    let months = end.getMonth() - start.getMonth();

    if (end.getDate() < start.getDate()) {
        months -= 1;
    }

    return years * 12 + months;
}

function rawQuantityForDocument(
    doc: EvaluatorApplicationDocument,
    quantidadeOverride?: number | null,
    periodOverride?: { data_inicio: string; data_fim: string } | null,
    windowEndIso?: string | null,
): number {
    const ti = doc.title_item;
    const period = periodOverride ?? {
        data_inicio: doc.data_inicio ?? '',
        data_fim: doc.data_fim ?? '',
    };

    if (
        usesPeriodDates(ti?.code) &&
        period.data_inicio &&
        period.data_fim &&
        windowEndIso
    ) {
        return quantityFromPeriodDates(
            period.data_inicio,
            period.data_fim,
            unitIsSemester(ti?.score_unit),
            windowEndIso,
            5,
        );
    }

    if (usesPeriodDates(ti?.code)) {
        const raw =
            quantidadeOverride != null
                ? quantidadeOverride
                : (doc.quantidade ?? 0);

        return Math.max(0, Number(raw));
    }

    const raw =
        quantidadeOverride != null
            ? quantidadeOverride
            : (doc.quantidade ?? 1);

    return Math.max(1, Number(raw));
}

/**
 * Rateio do teto max_quantity entre comprovantes do mesmo item (por id).
 */
export function effectiveQuantityForTitleDocument(
    doc: EvaluatorApplicationDocument,
    allDocuments: EvaluatorApplicationDocument[],
    quantidadeOverride?: number | null,
    periodOverride?: { data_inicio: string; data_fim: string } | null,
    windowEndIso?: string | null,
): number {
    const ti = doc.title_item;
    const raw = rawQuantityForDocument(
        doc,
        quantidadeOverride,
        periodOverride,
        windowEndIso,
    );

    if (ti?.max_quantity == null) {
        return raw;
    }

    const maxQuantity = Number(ti.max_quantity);
    const siblings = allDocuments
        .filter(
            (d) =>
                d.process_title_item_id === doc.process_title_item_id &&
                d.status !== 'recusado',
        )
        .sort((a, b) => a.id - b.id);

    let remaining = maxQuantity;

    for (const sibling of siblings) {
        const siblingRaw =
            sibling.id === doc.id
                ? raw
                : rawQuantityForDocument(sibling, null, null, windowEndIso);
        const allocated = Math.min(siblingRaw, remaining);
        remaining -= allocated;

        if (sibling.id === doc.id) {
            return allocated;
        }
    }

    return 0;
}

/**
 * Pontuação máxima permitida para uma linha de documento de titulação,
 * alinhada à regra do backend (score_per_unit × quantidade efetiva).
 */
export function maxPointsForTitleDocumentRow(
    doc: EvaluatorApplicationDocument,
    quantidadeOverride?: number | null,
    allDocuments: EvaluatorApplicationDocument[] = [],
    periodOverride?: { data_inicio: string; data_fim: string } | null,
    windowEndIso?: string | null,
): number | null {
    const ti = doc.title_item;

    if (ti == null || ti.score_per_unit == null || ti.score_per_unit === '') {
        return null;
    }

    const perUnit = Number(ti.score_per_unit);

    if (!Number.isFinite(perUnit) || perUnit < 0) {
        return null;
    }

    const docs =
        allDocuments.length > 0
            ? allDocuments
            : [doc];
    const qty = effectiveQuantityForTitleDocument(
        doc,
        docs,
        quantidadeOverride,
        periodOverride,
        windowEndIso,
    );

    if (qty <= 0) {
        return 0;
    }

    return Math.round(perUnit * qty * 100) / 100;
}

/**
 * Pontos efetivos ao aprovar, respeitando o teto do grupo (mesma regra do backend).
 */
export function resolvePointsForApprovedTitleDocument(
    doc: EvaluatorApplicationDocument,
    documentScores: Array<{
        application_document_id: number;
        pontuacao: number;
    }>,
    allDocuments: EvaluatorApplicationDocument[],
    quantidadeOverride?: number | null,
    periodOverride?: { data_inicio: string; data_fim: string } | null,
    windowEndIso?: string | null,
): number {
    const rowMax = maxPointsForTitleDocumentRow(
        doc,
        quantidadeOverride,
        allDocuments,
        periodOverride,
        windowEndIso,
    );

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
        .filter(
            (s) =>
                groupDocIds.has(s.application_document_id) &&
                s.application_document_id !== doc.id,
        )
        .reduce((sum, s) => sum + Number(s.pontuacao ?? 0), 0);

    const remaining = Math.max(0, groupMax - otherSum);

    return Math.round(Math.min(rowMax, remaining) * 100) / 100;
}
