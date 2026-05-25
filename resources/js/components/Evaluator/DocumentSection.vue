<script setup lang="ts">
import { ChevronUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DocumentReviewRow from '@/components/Evaluator/DocumentReviewRow.vue';
import type { EvaluatorApplicationDocument, EvaluatorDocumentSection } from '@/components/Evaluator/evaluatorDocumentTypes';

const COLLAPSED_LIMIT = 5;

const props = defineProps<{
    section: EvaluatorDocumentSection;
    applicationId: number;
    activeStatusFilter: string;
    activeSearch: string;
    documentScores?: Array<{ application_document_id: number; pontuacao: number }>;
    /** Soma dos pontos já atribuídos no grupo (titulação) vs. teto do grupo no edital. */
    titleGroupScoreCurrent?: number | null;
    titleGroupScoreMax?: number | null;
}>();

const emit = defineEmits<{
    'open-observation': [doc: EvaluatorApplicationDocument];
    'open-refuse': [doc: EvaluatorApplicationDocument];
    'patch-document-score': [payload: { application_document_id: number; pontuacao: number }];
    'document-decision-saved': [documentId: number, status: string];
}>();

const expanded = ref(false);

const filteredDocuments = computed(() => {
    let docs = props.section.documents;

    if (props.activeStatusFilter && props.activeStatusFilter !== 'all') {
        docs = docs.filter((d) => d.status === props.activeStatusFilter);
    }

    const q = props.activeSearch.trim().toLowerCase();
    if (q) {
        docs = docs.filter((d) => {
            const name = (d.required_document?.nome ?? d.title_item?.title ?? d.nome_arquivo).toLowerCase();
            return name.includes(q) || d.nome_arquivo.toLowerCase().includes(q);
        });
    }

    return docs;
});

const isLong = computed(() => filteredDocuments.value.length > COLLAPSED_LIMIT);
const showAll = ref(false);
const visibleDocuments = computed(() =>
    isLong.value && !showAll.value
        ? filteredDocuments.value.slice(0, COLLAPSED_LIMIT)
        : filteredDocuments.value,
);

const sectionStats = computed(() => {
    const docs = filteredDocuments.value;
    return {
        total: docs.length,
        approved: docs.filter((d) => d.status === 'aprovado').length,
        refused: docs.filter((d) => d.status === 'recusado').length,
        pending: docs.filter((d) => d.status !== 'aprovado' && d.status !== 'recusado').length,
    };
});
</script>

<template>
    <div
        v-if="filteredDocuments.length > 0"
        class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80 shadow-sm"
    >
        <!-- Section header -->
        <button
            type="button"
            class="flex w-full items-center gap-3 px-5 py-4 text-left transition-colors hover:bg-slate-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-teal-400/40"
            @click="expanded = !expanded"
        >
            <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
                    <h3 class="text-sm font-bold text-slate-900 sm:text-base">
                        {{ section.title }}
                    </h3>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">
                        {{ filteredDocuments.length }} {{ filteredDocuments.length === 1 ? 'item' : 'itens' }}
                    </span>
                    <div class="hidden items-center gap-2 sm:flex">
                        <span
                            v-if="sectionStats.approved > 0"
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200/60"
                        >
                            <span class="size-1.5 rounded-full bg-emerald-500" />
                            {{ sectionStats.approved }}
                        </span>
                        <span
                            v-if="sectionStats.pending > 0"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800 ring-1 ring-amber-200/60"
                        >
                            <span class="size-1.5 rounded-full bg-amber-500" />
                            {{ sectionStats.pending }}
                        </span>
                        <span
                            v-if="sectionStats.refused > 0"
                            class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-red-200/60"
                        >
                            <span class="size-1.5 rounded-full bg-red-500" />
                            {{ sectionStats.refused }}
                        </span>
                    </div>
                </div>
                <div
                    v-if="section.kind === 'titles' && titleGroupScoreMax != null"
                    class="shrink-0 rounded-lg bg-violet-50 px-2.5 py-1.5 text-[11px] font-semibold text-violet-800 ring-1 ring-violet-200/70 tabular-nums"
                >
                    Pontuação do grupo:
                    {{ (titleGroupScoreCurrent ?? 0).toFixed(2) }}
                    /
                    {{ Number(titleGroupScoreMax).toFixed(2) }}
                    pts
                </div>
            </div>
            <ChevronUp
                class="size-4 shrink-0 text-slate-400 transition-transform duration-200"
                :class="{ 'rotate-180': !expanded }"
            />
        </button>

        <!-- Table -->
        <div v-show="expanded" class="border-t border-slate-100">
            <div class="min-w-0 overflow-x-hidden">
                <table class="w-full table-fixed border-separate border-spacing-0">
                    <colgroup>
                        <col class="w-[38%]" />
                        <col class="w-[26%]" />
                        <col class="w-[14%]" />
                        <col class="w-[22%]" />
                    </colgroup>
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/70">
                            <th class="min-w-0 py-2.5 pl-5 pr-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                Documento
                            </th>
                            <th class="min-w-0 py-2.5 pr-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                Arquivo
                            </th>
                            <th class="min-w-0 py-2.5 pr-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                Status
                            </th>
                            <th class="min-w-0 py-2.5 pr-5 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                Ação
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <DocumentReviewRow
                            v-for="doc in visibleDocuments"
                            :key="doc.id"
                            :document="doc"
                            :application-id="applicationId"
                            :show-title-scoring="section.kind === 'titles'"
                            :document-scores="documentScores"
                            @open-observation="emit('open-observation', $event)"
                            @open-refuse="emit('open-refuse', $event)"
                            @patch-document-score="emit('patch-document-score', $event)"
                            @document-decision-saved="(id, status) => emit('document-decision-saved', id, status)"
                        />
                    </tbody>
                </table>
            </div>

            <!-- Show more / less toggle -->
            <div v-if="isLong" class="border-t border-slate-100 py-2 text-center">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
                    @click="showAll = !showAll"
                >
                    <ChevronUp
                        class="size-3.5 transition-transform duration-200"
                        :class="{ 'rotate-180': !showAll }"
                    />
                    {{ showAll ? 'Ver menos' : `Ver mais ${filteredDocuments.length - COLLAPSED_LIMIT} itens` }}
                </button>
            </div>
        </div>
    </div>
</template>
