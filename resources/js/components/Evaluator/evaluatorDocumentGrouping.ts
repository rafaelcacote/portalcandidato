import type { EvaluatorApplicationDocument, EvaluatorDocumentSection } from '@/components/Evaluator/evaluatorDocumentTypes';

function sortById(a: EvaluatorApplicationDocument, b: EvaluatorApplicationDocument): number {
    return a.id - b.id;
}

function titleItemOf(doc: EvaluatorApplicationDocument): EvaluatorApplicationDocument['title_item'] {
    return doc.title_item ?? null;
}

function sortTitleDocuments(docs: EvaluatorApplicationDocument[]): EvaluatorApplicationDocument[] {
    return [...docs].sort((a, b) => {
        const ta = titleItemOf(a);
        const tb = titleItemOf(b);
        const oa = ta?.order ?? 0;
        const ob = tb?.order ?? 0;
        if (oa !== ob) {
            return oa - ob;
        }
        const ca = ta?.code ?? '';
        const cb = tb?.code ?? '';
        if (ca !== cb) {
            return ca.localeCompare(cb, 'pt-BR');
        }

        return sortById(a, b);
    });
}

/**
 * Groups documents for evaluator review: required edital items, title proofs by group, special uploads, remainder.
 */
export function buildEvaluatorDocumentSections(
    documents: EvaluatorApplicationDocument[],
): EvaluatorDocumentSection[] {
    const sections: EvaluatorDocumentSection[] = [];

    const required = documents.filter(
        (d) => d.required_document != null || (d.process_required_document_id != null && d.process_required_document_id > 0),
    );
    if (required.length > 0) {
        sections.push({
            key: 'required',
            title: 'Documentação obrigatória',
            description:
                'Itens exigidos pelo edital. Confira legibilidade, prazo e conformidade com o solicitado.',
            kind: 'required',
            documents: [...required].sort(sortById),
        });
    }

    const titleLinked = documents.filter((d) => d.process_title_item_id != null && d.process_title_item_id > 0);
    const byGroup = new Map<
        number,
        { meta: { id: number; name: string; description: string | null; order: number }; docs: EvaluatorApplicationDocument[] }
    >();
    const orphanTitles: EvaluatorApplicationDocument[] = [];

    for (const doc of titleLinked) {
        const ti = titleItemOf(doc);
        const tg = ti?.title_group;
        if (tg != null && typeof tg.id === 'number') {
            const order = tg.order ?? 0;
            if (!byGroup.has(tg.id)) {
                byGroup.set(tg.id, {
                    meta: {
                        id: tg.id,
                        name: tg.name,
                        description: tg.description ?? null,
                        order,
                    },
                    docs: [],
                });
            }
            byGroup.get(tg.id)!.docs.push(doc);
        } else {
            orphanTitles.push(doc);
        }
    }

    const sortedGroups = [...byGroup.values()].sort((a, b) => {
        if (a.meta.order !== b.meta.order) {
            return a.meta.order - b.meta.order;
        }

        return a.meta.name.localeCompare(b.meta.name, 'pt-BR');
    });

    for (const g of sortedGroups) {
        sections.push({
            key: `title-group-${g.meta.id}`,
            title: g.meta.name,
            description: g.meta.description,
            kind: 'titles',
            documents: sortTitleDocuments(g.docs),
        });
    }

    if (orphanTitles.length > 0) {
        sections.push({
            key: 'title-items-ungrouped',
            title: 'Comprovantes de títulos',
            description: 'Anexos da pontuação de títulos (sem grupo cadastrado ou metadados incompletos).',
            kind: 'titles',
            documents: sortTitleDocuments(orphanTitles),
        });
    }

    const special = documents.filter(
        (d) =>
            Boolean(d.candidatura_document_kind) &&
            (d.process_required_document_id == null || d.process_required_document_id === 0) &&
            (d.process_title_item_id == null || d.process_title_item_id === 0),
    );
    if (special.length > 0) {
        sections.push({
            key: 'special',
            title: 'Documentação complementar',
            description: 'Anexos especiais da inscrição (ex.: deficiência, declarações).',
            kind: 'special',
            documents: [...special].sort(sortById),
        });
    }

    const accountedIds = new Set<number>();
    for (const s of sections) {
        for (const d of s.documents) {
            accountedIds.add(d.id);
        }
    }
    const other = documents.filter((d) => !accountedIds.has(d.id));
    if (other.length > 0) {
        sections.push({
            key: 'other',
            title: 'Demais anexos',
            description: 'Itens que não se encaixaram nas categorias acima — valide normalmente.',
            kind: 'other',
            documents: [...other].sort(sortById),
        });
    }

    return sections;
}
