<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ClipboardCheck } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CandidateHeroCard from '@/components/Evaluator/CandidateHeroCard.vue';
import CandidateReviewActions from '@/components/Evaluator/CandidateReviewActions.vue';
import CandidateScoreSection from '@/components/Evaluator/CandidateScoreSection.vue';
import CandidateStatusBadge from '@/components/Evaluator/CandidateStatusBadge.vue';
import DocumentSection from '@/components/Evaluator/DocumentSection.vue';
import EvaluationSummary from '@/components/Evaluator/EvaluationSummary.vue';
import { buildEvaluatorDocumentSections } from '@/components/Evaluator/evaluatorDocumentGrouping';
import type {
    EvaluatorApplicationDocument,
    EvaluatorDocumentSection,
} from '@/components/Evaluator/evaluatorDocumentTypes';
import { resolvePointsForApprovedTitleDocument } from '@/components/Evaluator/evaluatorTitleScoring';
import ObservationDialog from '@/components/Evaluator/ObservationDialog.vue';
import { home } from '@/routes';
import { dashboard } from '@/routes/evaluator';
import scoreRoutes from '@/routes/evaluator/candidates/score';
import {
    index as processesIndex,
    show as processShow,
} from '@/routes/evaluator/processes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel do avaliador', href: dashboard.url() },
            { title: 'Processos', href: processesIndex().url },
        ],
    },
});

const props = defineProps<{
    application: {
        id: number;
        status: string;
        numero_protocolo: string | null;
        created_at: string;
        user: {
            id: number;
            name: string;
            email: string;
            cpf?: string | null;
            telefone?: string | null;
            foto_url?: string | null;
            photo_url?: string | null;
        };
        selectionProcess?: {
            id: number;
            titulo: string;
            inscricao_fim_em?: string | null;
            criteria?: Array<{
                id: number;
                nome: string;
                peso: number;
                pontuacao_max: number;
            }>;
        } | null;
        documents?: EvaluatorApplicationDocument[];
        evaluations?: Array<{
            id: number;
            resultado: string | null;
            pontuacao_total: number | null;
            observacoes?: string | null;
            scores?: Array<{ process_criteria_id: number; pontuacao: number }>;
            document_scores?: Array<{
                application_document_id: number;
                pontuacao: number;
            }>;
        }>;
        research_line_summary?: {
            linha_pesquisa: string;
            linha_pesquisa_label: string;
            orientador: string;
        } | null;
        employment_relationship_summary?: {
            concorre_vagas_sem_vinculo: boolean;
            resposta_label: string;
        } | null;
    };
    can_evaluate: boolean;
}>();

// ── Document sections ────────────────────────────────────────────────────────
const allDocuments = computed(() => props.application.documents ?? []);
const sections = computed(() =>
    buildEvaluatorDocumentSections(allDocuments.value),
);

const periodWindowEnd = computed(() => {
    const processEnd = props.application.selectionProcess?.inscricao_fim_em;

    if (processEnd) {
        return String(processEnd).slice(0, 10);
    }

    const finalized = (props.application as { finalizada_em?: string | null })
        .finalizada_em;

    if (finalized) {
        return String(finalized).slice(0, 10);
    }

    return new Date().toISOString().slice(0, 10);
});

const searchQuery = ref('');
const statusFilter = ref('all');
const categoryFilter = ref('all');

const categoryOptions = computed(() => {
    const base = [{ label: 'Todas as categorias', value: 'all' }];
    sections.value.forEach((s) => {
        base.push({ label: s.title, value: s.key });
    });

    return base;
});

const visibleSections = computed(() => {
    if (categoryFilter.value === 'all') {
        return sections.value;
    }

    return sections.value.filter((s) => s.key === categoryFilter.value);
});

// ── Observation dialog ───────────────────────────────────────────────────────
const obsDialogVisible = ref(false);
const obsDocument = ref<EvaluatorApplicationDocument | null>(null);
const obsPendingStatus = ref<'recusado' | 'aprovado' | null>(null);

function openObservation(doc: EvaluatorApplicationDocument): void {
    obsDocument.value = doc;
    obsPendingStatus.value = null;
    obsDialogVisible.value = true;
}

function openRefuse(doc: EvaluatorApplicationDocument): void {
    obsDocument.value = doc;
    obsPendingStatus.value = 'recusado';
    obsDialogVisible.value = true;
}

function buildInitialDocumentScores(): Array<{
    application_document_id: number;
    pontuacao: number;
}> {
    const docs = (props.application.documents ?? []).filter(
        (d) => d.process_title_item_id != null && d.process_title_item_id > 0,
    );
    const eval0 = props.application.evaluations?.[0];
    const scores: Array<{
        application_document_id: number;
        pontuacao: number;
    }> = [];

    for (const doc of docs) {
        const existing = eval0?.document_scores?.find(
            (s) => s.application_document_id === doc.id,
        );

        if (existing != null) {
            scores.push({
                application_document_id: doc.id,
                pontuacao: Number(existing.pontuacao ?? 0),
            });
            continue;
        }

        if (doc.status === 'aprovado') {
            scores.push({
                application_document_id: doc.id,
                pontuacao: resolvePointsForApprovedTitleDocument(
                    doc,
                    scores,
                    docs,
                    null,
                    null,
                    periodWindowEnd.value,
                ),
            });
        } else {
            scores.push({
                application_document_id: doc.id,
                pontuacao: 0,
            });
        }
    }

    return scores;
}

function syncDocumentScoresFromProps(): void {
    scoreForm.document_scores = buildInitialDocumentScores();
}

// ── Score form ───────────────────────────────────────────────────────────────
const criteria = computed(
    () => props.application.selectionProcess?.criteria ?? [],
);
const existingEvaluation = computed(() => props.application.evaluations?.[0]);

const scoreForm = useForm({
    scores: criteria.value.map((item) => {
        const existing = existingEvaluation.value?.scores?.find(
            (s) => s.process_criteria_id === item.id,
        );

        return {
            process_criteria_id: item.id,
            pontuacao: existing?.pontuacao ?? 0,
        };
    }),
    document_scores: buildInitialDocumentScores(),
    resultado: existingEvaluation.value?.resultado ?? 'classificado',
    observacoes: existingEvaluation.value?.observacoes ?? '',
});

const titulacaoMaxTotal = computed(() => {
    const seen = new Set<number>();
    let sum = 0;

    for (const d of allDocuments.value) {
        if (!d.process_title_item_id) {
            continue;
        }

        const gid = d.title_item?.title_group?.id;

        if (gid == null || seen.has(gid)) {
            continue;
        }

        seen.add(gid);
        const m = d.title_item?.title_group?.max_score;

        if (m != null && m !== '') {
            sum += Number(m);
        }
    }

    return sum;
});

const documentScoresSum = computed(() =>
    scoreForm.document_scores.reduce((a, s) => a + Number(s.pontuacao), 0),
);

const displayedPontuacaoTotal = computed(() => {
    const saved = existingEvaluation.value?.pontuacao_total;

    if (saved != null && saved !== '') {
        return Number(saved);
    }

    const criteriaPart = scoreForm.scores.reduce(
        (a, s) => a + Number(s.pontuacao ?? 0),
        0,
    );

    return criteriaPart + documentScoresSum.value;
});

watch(
    () => [
        props.application.evaluations?.[0]?.document_scores,
        props.application.evaluations?.[0]?.pontuacao_total,
        (props.application.documents ?? [])
            .map((d) => `${d.id}:${d.status}:${d.quantidade ?? 1}`)
            .join('|'),
    ],
    () => {
        syncDocumentScoresFromProps();
    },
    { deep: true },
);

function titleGroupStatsForSection(
    section: EvaluatorDocumentSection,
): { current: number; max: number } | undefined {
    if (section.kind !== 'titles' || section.documents.length === 0) {
        return undefined;
    }

    const g = section.documents[0].title_item?.title_group;

    if (g?.max_score == null || g.max_score === '') {
        return undefined;
    }

    const ids = new Set(section.documents.map((d) => d.id));
    const current = scoreForm.document_scores
        .filter((s) => ids.has(s.application_document_id))
        .reduce((a, s) => a + Number(s.pontuacao), 0);

    return { current, max: Number(g.max_score) };
}

function patchDocumentScore(payload: {
    application_document_id: number;
    pontuacao: number;
}): void {
    const idx = scoreForm.document_scores.findIndex(
        (r) => r.application_document_id === payload.application_document_id,
    );

    if (idx === -1) {
        return;
    }

    scoreForm.document_scores[idx] = {
        application_document_id: payload.application_document_id,
        pontuacao: payload.pontuacao,
    };
}

function applyAutoScoreForDocument(documentId: number, status: string): void {
    const doc = allDocuments.value.find((d) => d.id === documentId);

    if (doc == null || !doc.process_title_item_id) {
        return;
    }

    const points =
        status === 'aprovado'
            ? resolvePointsForApprovedTitleDocument(
                  doc,
                  scoreForm.document_scores,
                  allDocuments.value,
                  null,
                  null,
                  periodWindowEnd.value,
              )
            : 0;

    patchDocumentScore({
        application_document_id: documentId,
        pontuacao: points,
    });
}

function handleDocumentPeriodUpdated(
    documentId: number,
    payload: { data_inicio: string; data_fim: string; quantidade: number },
): void {
    const doc = allDocuments.value.find((d) => d.id === documentId);

    if (doc == null || !doc.process_title_item_id) {
        return;
    }

    if (doc.status !== 'aprovado') {
        return;
    }

    const points = resolvePointsForApprovedTitleDocument(
        doc,
        scoreForm.document_scores,
        allDocuments.value,
        null,
        {
            data_inicio: payload.data_inicio,
            data_fim: payload.data_fim,
        },
        periodWindowEnd.value,
    );

    patchDocumentScore({
        application_document_id: documentId,
        pontuacao: points,
    });
}

function handleScoresUpdate(
    scores: Array<{ process_criteria_id: number; pontuacao: number }>,
): void {
    scoreForm.scores = scores;
}

function handleObservacoesUpdate(value: string): void {
    scoreForm.observacoes = value;
}

function saveDraft(): void {
    scoreForm.post(scoreRoutes.store(props.application.id).url, {
        preserveScroll: true,
    });
}

function finalize(): void {
    scoreForm.post(scoreRoutes.store(props.application.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50/60">
        <Head
            :title="
                can_evaluate
                    ? `Avaliação – ${application.user.name}`
                    : `Visualização – ${application.user.name}`
            "
        />

        <!-- ── Scrollable content ── -->
        <div
            class="flex-1 px-3 pt-4 sm:px-6 sm:pt-5 lg:px-8"
            :class="can_evaluate ? 'pb-24 sm:pb-28' : 'pb-8'"
        >
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 sm:gap-5">
                <!-- Page header -->
                <div class="flex flex-col gap-2">
                    <Link
                        :href="
                            application.selectionProcess
                                ? processShow({
                                      selectionProcess:
                                          application.selectionProcess.id,
                                  }).url
                                : processesIndex().url
                        "
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 transition-colors hover:text-teal-600"
                    >
                        <ChevronLeft class="size-3.5" />
                        Voltar para candidatos
                    </Link>

                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600"
                            >
                                <ClipboardCheck class="size-4.5" />
                            </div>
                            <div>
                                <h1
                                    class="text-base font-bold tracking-tight text-slate-900 sm:text-lg"
                                >
                                    {{
                                        can_evaluate
                                            ? 'Avaliação de Candidato'
                                            : 'Visualização de Candidato'
                                    }}
                                </h1>
                                <p class="text-xs text-slate-500">
                                    {{
                                        can_evaluate
                                            ? 'Valide os documentos enviados e registre sua avaliação.'
                                            : 'Consulte os dados e documentos enviados até o momento.'
                                    }}
                                </p>
                            </div>
                        </div>

                        <CandidateStatusBadge
                            :status="application.status"
                            size="md"
                        />
                    </div>
                </div>

                <div
                    v-if="!can_evaluate"
                    class="rounded-2xl border border-amber-200/80 bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200/60"
                    role="status"
                >
                    Inscrição em andamento. A avaliação ficará disponível após
                    o candidato finalizar a inscrição.
                </div>

                <!-- Candidate card -->
                <CandidateHeroCard :application="application" />

                <!-- Summary + filters -->
                <EvaluationSummary
                    :documents="allDocuments"
                    :search="searchQuery"
                    :status-filter="statusFilter"
                    :category-filter="categoryFilter"
                    :category-options="categoryOptions"
                    :pontuacao-total="displayedPontuacaoTotal"
                    @update:search="searchQuery = $event"
                    @update:status-filter="statusFilter = $event"
                    @update:category-filter="categoryFilter = $event"
                />

                <!-- Document sections -->
                <DocumentSection
                    v-for="section in visibleSections"
                    :key="section.key"
                    :section="section"
                    :application-id="application.id"
                    :read-only="!can_evaluate"
                    :active-status-filter="statusFilter"
                    :active-search="searchQuery"
                    :document-scores="scoreForm.document_scores"
                    :title-group-score-current="
                        titleGroupStatsForSection(section)?.current ?? null
                    "
                    :title-group-score-max="
                        titleGroupStatsForSection(section)?.max ?? null
                    "
                    :all-documents="allDocuments"
                    :period-window-end="periodWindowEnd"
                    @open-observation="openObservation"
                    @open-refuse="openRefuse"
                    @patch-document-score="patchDocumentScore"
                    @document-decision-saved="applyAutoScoreForDocument"
                    @document-period-updated="handleDocumentPeriodUpdated"
                />

                <!-- Score section -->
                <CandidateScoreSection
                    v-if="
                        can_evaluate &&
                        (criteria.length > 0 || titulacaoMaxTotal > 0)
                    "
                    :criteria="criteria"
                    :scores="scoreForm.scores"
                    :observacoes="scoreForm.observacoes"
                    :title-scoring-current="documentScoresSum"
                    :title-scoring-max="titulacaoMaxTotal"
                    @update:scores="handleScoresUpdate"
                    @update:observacoes="handleObservacoesUpdate"
                />
            </div>
        </div>

        <!-- ── Sticky footer ── -->
        <CandidateReviewActions
            v-if="can_evaluate"
            :processing="scoreForm.processing"
            @save-draft="saveDraft"
            @finalize="finalize"
        />

        <!-- ── Observation / refuse dialog ── -->
        <ObservationDialog
            v-if="can_evaluate"
            :document="obsDocument"
            :application-id="application.id"
            :visible="obsDialogVisible"
            :pending-status="obsPendingStatus"
            @update:visible="obsDialogVisible = $event"
            @saved="
                (docId, status) => {
                    applyAutoScoreForDocument(docId, status);
                    obsDialogVisible = false;
                }
            "
        />
    </div>
</template>
