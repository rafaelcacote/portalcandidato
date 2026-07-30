<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check, Eye, FileText, MessageSquare, X } from 'lucide-vue-next';
import Tooltip from 'primevue/tooltip';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import CandidateStatusBadge from '@/components/Evaluator/CandidateStatusBadge.vue';
import type { EvaluatorApplicationDocument } from '@/components/Evaluator/evaluatorDocumentTypes';
import {
    maxPointsForTitleDocumentRow,
    periodExceedsWindow,
    quantityFromPeriodDates,
    quantityLabelForScoreUnit,
    unitIsSemester,
    usesPeriodDates,
} from '@/components/Evaluator/evaluatorTitleScoring';
import evaluatorDocuments, {
    period as updatePeriodRoute,
    view as viewDocument,
} from '@/routes/evaluator/candidates/documents';

const vTooltip = Tooltip;

const props = defineProps<{
    document: EvaluatorApplicationDocument;
    applicationId: number;
    readOnly?: boolean;
    showTitleScoring?: boolean;
    documentScores?: Array<{
        application_document_id: number;
        pontuacao: number;
    }>;
    allDocuments?: EvaluatorApplicationDocument[];
    periodWindowEnd?: string | null;
}>();

const emit = defineEmits<{
    'open-observation': [doc: EvaluatorApplicationDocument];
    'open-refuse': [doc: EvaluatorApplicationDocument];
    'patch-document-score': [
        payload: { application_document_id: number; pontuacao: number },
    ];
    'document-decision-saved': [documentId: number, status: string];
    'document-period-updated': [
        documentId: number,
        payload: { data_inicio: string; data_fim: string; quantidade: number },
    ];
}>();

function toDateInputValue(value?: string | null): string {
    if (!value) {
        return '';
    }

    return value.slice(0, 10);
}

/** Só os títulos B por período (B1, B2, B3, B5, B6, B7) usam data início/fim. */
const isPeriodTitle = computed(() =>
    usesPeriodDates(props.document.title_item?.code),
);

const form = useForm({
    status: props.document.status,
    motivo_recusa: props.document.motivo_recusa ?? '',
    data_inicio: toDateInputValue(props.document.data_inicio),
    data_fim: toDateInputValue(props.document.data_fim),
});

const periodForm = useForm({
    data_inicio: toDateInputValue(props.document.data_inicio),
    data_fim: toDateInputValue(props.document.data_fim),
});

const localDataInicio = ref(toDateInputValue(props.document.data_inicio));
const localDataFim = ref(toDateInputValue(props.document.data_fim));
const syncingFromProps = ref(false);
let periodSaveTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => [props.document.data_inicio, props.document.data_fim],
    () => {
        syncingFromProps.value = true;
        localDataInicio.value = toDateInputValue(props.document.data_inicio);
        localDataFim.value = toDateInputValue(props.document.data_fim);
        periodForm.data_inicio = localDataInicio.value;
        periodForm.data_fim = localDataFim.value;
        form.data_inicio = localDataInicio.value;
        form.data_fim = localDataFim.value;
        queueMicrotask(() => {
            syncingFromProps.value = false;
        });
    },
);

const siblingDocuments = computed(
    () => props.allDocuments ?? [props.document],
);

const periodOverride = computed(() => {
    if (!isPeriodTitle.value || !localDataInicio.value || !localDataFim.value) {
        return null;
    }

    return {
        data_inicio: localDataInicio.value,
        data_fim: localDataFim.value,
    };
});

const calculatedRawQuantity = computed(() => {
    if (
        !isPeriodTitle.value ||
        !localDataInicio.value ||
        !localDataFim.value ||
        !props.periodWindowEnd
    ) {
        return null;
    }

    return quantityFromPeriodDates(
        localDataInicio.value,
        localDataFim.value,
        unitIsSemester(props.document.title_item?.score_unit),
        props.periodWindowEnd,
        5,
    );
});

/** Data a partir da qual o edital considera a atividade (hoje/fim das inscrições - 5 anos). */
const windowStartLabel = computed(() => {
    if (!props.periodWindowEnd) {
        return null;
    }

    const windowEnd = new Date(props.periodWindowEnd.slice(0, 10));

    if (Number.isNaN(windowEnd.getTime())) {
        return null;
    }

    windowEnd.setFullYear(windowEnd.getFullYear() - 5);

    return windowEnd.toLocaleDateString('pt-BR');
});

/** Aviso: parte do período informado está fora da janela dos últimos 5 anos do edital. */
const periodOutsideWindowWarning = computed(() => {
    if (
        !isPeriodTitle.value ||
        !localDataInicio.value ||
        !localDataFim.value ||
        !props.periodWindowEnd
    ) {
        return false;
    }

    return periodExceedsWindow(
        localDataInicio.value,
        localDataFim.value,
        props.periodWindowEnd,
        5,
    );
});

function approve(): void {
    if (periodSaveTimer !== null) {
        clearTimeout(periodSaveTimer);
        periodSaveTimer = null;
    }

    form.status = 'aprovado';
    form.motivo_recusa = '';

    if (isPeriodTitle.value && localDataInicio.value && localDataFim.value) {
        form.data_inicio = localDataInicio.value;
        form.data_fim = localDataFim.value;
    } else {
        form.data_inicio = '';
        form.data_fim = '';
    }

    form.post(
        evaluatorDocuments.decision({
            application: props.applicationId,
            applicationDocument: props.document.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('document-decision-saved', props.document.id, 'aprovado');
            },
        },
    );
}

function savePeriod(): void {
    if (props.readOnly || !props.showTitleScoring || !isPeriodTitle.value) {
        return;
    }

    if (!localDataInicio.value || !localDataFim.value) {
        return;
    }

    if (localDataFim.value < localDataInicio.value) {
        return;
    }

    if (
        localDataInicio.value === toDateInputValue(props.document.data_inicio) &&
        localDataFim.value === toDateInputValue(props.document.data_fim)
    ) {
        return;
    }

    periodForm.data_inicio = localDataInicio.value;
    periodForm.data_fim = localDataFim.value;
    periodForm.patch(
        updatePeriodRoute({
            application: props.applicationId,
            applicationDocument: props.document.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('document-period-updated', props.document.id, {
                    data_inicio: localDataInicio.value,
                    data_fim: localDataFim.value,
                    quantidade: calculatedRawQuantity.value ?? 0,
                });
            },
        },
    );
}

function schedulePeriodSave(): void {
    if (syncingFromProps.value || props.readOnly || !isPeriodTitle.value) {
        return;
    }

    if (periodSaveTimer !== null) {
        clearTimeout(periodSaveTimer);
    }

    periodSaveTimer = setTimeout(() => {
        periodSaveTimer = null;
        savePeriod();
    }, 450);
}

onBeforeUnmount(() => {
    if (periodSaveTimer !== null) {
        clearTimeout(periodSaveTimer);
    }
});

watch([localDataInicio, localDataFim], () => {
    schedulePeriodSave();
});

const viewUrl = computed(
    () =>
        viewDocument({
            application: props.applicationId,
            applicationDocument: props.document.id,
        }).url,
);

const heading = computed(() => {
    const d = props.document;

    if (d.required_document?.nome) {
        return d.required_document.nome;
    }

    if (d.title_item?.title) {
        const code = d.title_item.code ? `${d.title_item.code} – ` : '';

        return `${code}${d.title_item.title}`;
    }

    if (d.candidatura_document_kind) {
        const kindMap: Record<string, string> = {
            pcd_declaracao: 'Declaração PcD',
            pcd_laudo: 'Laudo médico (PcD)',
        };

        return (
            kindMap[d.candidatura_document_kind] ?? d.candidatura_document_kind
        );
    }

    return d.nome_arquivo;
});

const subtext = computed(() => props.document.required_document?.descricao ?? null);

const hasObservation = computed(() =>
    Boolean(props.document.motivo_recusa?.trim()),
);

const quantityLabel = computed(() =>
    quantityLabelForScoreUnit(props.document.title_item?.score_unit),
);

const titleRowMax = computed(() =>
    props.showTitleScoring
        ? maxPointsForTitleDocumentRow(
              props.document,
              null,
              siblingDocuments.value,
              periodOverride.value,
              props.periodWindowEnd,
          )
        : null,
);

const periodScorePreviewLabel = computed(() => {
    const perUnit = Number(props.document.title_item?.score_per_unit ?? 0);

    if (
        !isPeriodTitle.value ||
        !Number.isFinite(perUnit) ||
        perUnit <= 0 ||
        titleRowMax.value == null ||
        calculatedRawQuantity.value == null
    ) {
        return null;
    }

    return `${calculatedRawQuantity.value} × ${perUnit} = ${titleRowMax.value.toFixed(2)} pts`;
});

const titleScoreModel = computed(() => {
    const row = props.documentScores?.find(
        (s) => s.application_document_id === props.document.id,
    );

    return Number(row?.pontuacao ?? 0);
});

const showScoringPanel = computed(
    () => props.showTitleScoring && titleRowMax.value != null,
);

function getFileIconClass(filename: string): string {
    const ext = filename.split('.').pop()?.toLowerCase() ?? '';
    const map: Record<string, string> = {
        pdf: 'bg-red-50 text-red-500',
        doc: 'bg-blue-50 text-blue-500',
        docx: 'bg-blue-50 text-blue-500',
        xls: 'bg-emerald-50 text-emerald-600',
        xlsx: 'bg-emerald-50 text-emerald-600',
        jpg: 'bg-violet-50 text-violet-500',
        jpeg: 'bg-violet-50 text-violet-500',
        png: 'bg-violet-50 text-violet-500',
    };

    return map[ext] ?? 'bg-slate-100 text-slate-500';
}
</script>

<template>
    <tr
        class="group border-b border-slate-100 transition-colors last:border-0 hover:bg-slate-50/60"
    >
        <td class="min-w-0 py-3 pr-3 pl-5 align-top">
            <div class="flex items-start gap-3">
                <div
                    :class="[
                        'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg',
                        getFileIconClass(document.nome_arquivo),
                    ]"
                >
                    <FileText class="size-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <p
                        class="text-sm leading-snug font-semibold [overflow-wrap:anywhere] text-slate-800 sm:leading-normal"
                        :title="heading"
                    >
                        {{ heading }}
                    </p>
                    <p
                        v-if="subtext"
                        class="mt-1 line-clamp-2 text-xs leading-relaxed [overflow-wrap:anywhere] text-slate-400 sm:line-clamp-3"
                    >
                        {{ subtext }}
                    </p>
                    <p
                        v-else-if="document.title_item"
                        class="mt-1 text-xs leading-relaxed [overflow-wrap:anywhere] text-slate-500"
                    >
                        <template
                            v-if="
                                document.title_item.score_per_unit != null &&
                                document.title_item.score_per_unit !== ''
                            "
                        >
                            {{ Number(document.title_item.score_per_unit) }}
                            pt(s)
                            <span v-if="document.title_item.score_unit"
                                >· {{ document.title_item.score_unit }}</span
                            >
                        </template>
                        <template v-else>Comprovante de titulação</template>
                    </p>
                    <p v-else class="mt-1 text-xs text-slate-400">
                        Documento obrigatório
                    </p>

                    <div
                        v-if="showScoringPanel"
                        class="mt-2 flex flex-col gap-2 rounded-lg bg-violet-50/80 px-2.5 py-2 ring-1 ring-violet-200/60"
                    >
                        <div
                            v-if="!readOnly && isPeriodTitle"
                            class="flex flex-col gap-2"
                        >
                            <div class="flex flex-wrap items-end gap-2">
                                <label
                                    class="flex flex-col gap-1 text-[11px] font-semibold text-violet-800"
                                >
                                    Data inicial
                                    <input
                                        v-model="localDataInicio"
                                        type="date"
                                        class="rounded-md border border-violet-200 bg-white px-2 py-1 text-xs font-medium text-slate-800"
                                        :disabled="periodForm.processing"
                                    />
                                </label>
                                <label
                                    class="flex flex-col gap-1 text-[11px] font-semibold text-violet-800"
                                >
                                    Data final
                                    <input
                                        v-model="localDataFim"
                                        type="date"
                                        class="rounded-md border border-violet-200 bg-white px-2 py-1 text-xs font-medium text-slate-800"
                                        :disabled="periodForm.processing"
                                    />
                                </label>
                            </div>
                            <p
                                v-if="
                                    localDataInicio &&
                                    localDataFim &&
                                    calculatedRawQuantity != null
                                "
                                class="text-[11px] font-medium text-violet-700 tabular-nums"
                            >
                                {{ quantityLabel }} calculados:
                                {{ calculatedRawQuantity }}
                                <span v-if="periodScorePreviewLabel">
                                    · {{ periodScorePreviewLabel }}
                                </span>
                            </p>
                            <p v-else class="text-[11px] text-violet-600">
                                Informe o período do comprovante para calcular
                                {{ quantityLabel.toLowerCase() }}.
                            </p>
                            <p
                                v-if="periodOutsideWindowWarning"
                                class="flex items-start gap-1 text-[11px] font-medium text-amber-700"
                            >
                                <span aria-hidden="true">⚠</span>
                                <span>
                                    O edital só considera os últimos 5 anos
                                    (a partir de {{ windowStartLabel }}). A
                                    parte do período fora dessa janela não
                                    será contabilizada.
                                </span>
                            </p>
                            <small
                                v-if="periodForm.errors.data_inicio"
                                class="text-[11px] font-medium text-red-600"
                            >
                                {{ periodForm.errors.data_inicio }}
                            </small>
                            <small
                                v-if="periodForm.errors.data_fim"
                                class="text-[11px] font-medium text-red-600"
                            >
                                {{ periodForm.errors.data_fim }}
                            </small>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <template v-if="document.status === 'aprovado'">
                                <span
                                    class="text-[11px] font-semibold text-violet-800"
                                    >Pontuação aplicada</span
                                >
                                <span
                                    class="rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-emerald-700 tabular-nums ring-1 ring-emerald-200/80"
                                >
                                    {{ titleScoreModel.toFixed(2) }} pts
                                </span>
                                <span
                                    v-if="(titleRowMax ?? 0) > 0"
                                    class="text-[11px] font-medium text-violet-600"
                                >
                                    (máx. {{ titleRowMax }} no edital)
                                </span>
                            </template>
                            <template v-else-if="(titleRowMax ?? 0) > 0">
                                <span
                                    class="text-[11px] font-medium text-violet-700"
                                >
                                    Ao aprovar: até {{ titleRowMax }} pts
                                    conforme o edital
                                </span>
                            </template>
                            <template v-else-if="isPeriodTitle">
                                <span
                                    class="text-[11px] font-medium text-violet-700"
                                >
                                    Período incompleto ou fora da janela do
                                    edital — 0 pts
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </td>

        <td class="min-w-0 py-3 pr-3 align-top">
            <p
                class="line-clamp-2 font-mono text-xs leading-relaxed [overflow-wrap:anywhere] text-slate-500"
                :title="document.nome_arquivo"
            >
                {{ document.nome_arquivo }}
            </p>
        </td>

        <td class="min-w-0 py-3 pr-3 align-top">
            <CandidateStatusBadge :status="document.status" size="sm" />
        </td>

        <td class="min-w-0 py-3 pr-5 align-top">
            <div
                class="flex max-w-full flex-wrap items-center justify-end gap-1.5"
            >
                <a
                    v-tooltip.top="'Visualizar arquivo'"
                    :href="viewUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex size-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition-all duration-150 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 focus-visible:ring-2 focus-visible:ring-teal-400/40 focus-visible:outline-none"
                >
                    <Eye class="size-3.5" />
                </a>

                <button
                    v-if="!readOnly"
                    v-tooltip.top="'Aprovar'"
                    type="button"
                    :disabled="form.processing"
                    :class="[
                        'flex size-8 items-center justify-center rounded-lg transition-all duration-150 focus-visible:ring-2 focus-visible:outline-none',
                        document.status === 'aprovado'
                            ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-400/40'
                            : 'border border-slate-200 bg-white text-slate-400 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 focus-visible:ring-emerald-400/40',
                    ]"
                    @click="approve"
                >
                    <Check class="size-3.5" />
                </button>

                <button
                    v-if="!readOnly"
                    v-tooltip.top="'Recusar (requer justificativa)'"
                    type="button"
                    :disabled="form.processing"
                    :class="[
                        'flex size-8 items-center justify-center rounded-lg transition-all duration-150 focus-visible:ring-2 focus-visible:outline-none',
                        document.status === 'recusado'
                            ? 'bg-red-500 text-white shadow-sm shadow-red-400/40'
                            : 'border border-slate-200 bg-white text-slate-400 hover:border-red-300 hover:bg-red-50 hover:text-red-700 focus-visible:ring-red-400/40',
                    ]"
                    @click="emit('open-refuse', document)"
                >
                    <X class="size-3.5" />
                </button>

                <button
                    v-if="!readOnly"
                    v-tooltip.top="
                        hasObservation
                            ? 'Editar observação'
                            : 'Adicionar observação'
                    "
                    type="button"
                    :class="[
                        'relative flex size-8 items-center justify-center rounded-lg border transition-all duration-150 focus-visible:ring-2 focus-visible:outline-none',
                        hasObservation
                            ? 'border-amber-300 bg-amber-50 text-amber-600 hover:bg-amber-100 focus-visible:ring-amber-400/40'
                            : 'border-slate-200 bg-white text-slate-400 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600 focus-visible:ring-amber-400/40',
                    ]"
                    @click="emit('open-observation', document)"
                >
                    <MessageSquare class="size-3.5" />
                    <span
                        v-if="hasObservation"
                        class="absolute -top-0.5 -right-0.5 size-2 rounded-full bg-amber-500 ring-1 ring-white"
                    />
                </button>
            </div>
        </td>
    </tr>
</template>
