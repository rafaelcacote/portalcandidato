<script setup lang="ts">
import {
    Accessibility,
    Award,
    CheckCircle2,
    Clock,
    FileCheck,
    FileStack,
    FolderOpen,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import CandidateDocumentItem from '@/components/Evaluator/CandidateDocumentItem.vue';
import { buildEvaluatorDocumentSections } from '@/components/Evaluator/evaluatorDocumentGrouping';
import type { EvaluatorApplicationDocument } from '@/components/Evaluator/evaluatorDocumentTypes';

const props = defineProps<{
    documents: EvaluatorApplicationDocument[];
    applicationId: number;
}>();

const sections = computed(() =>
    buildEvaluatorDocumentSections(props.documents ?? []),
);

const globalStats = computed(() => {
    const docs = props.documents ?? [];
    const total = docs.length;
    const approved = docs.filter((d) => d.status === 'aprovado').length;
    const refused = docs.filter((d) => d.status === 'recusado').length;
    const pending = docs.filter(
        (d) => d.status !== 'aprovado' && d.status !== 'recusado',
    ).length;

    return { total, approved, refused, pending };
});

function sectionStats(docs: EvaluatorApplicationDocument[]): {
    approved: number;
    refused: number;
    pending: number;
} {
    const approved = docs.filter((d) => d.status === 'aprovado').length;
    const refused = docs.filter((d) => d.status === 'recusado').length;
    const pending = docs.filter(
        (d) => d.status !== 'aprovado' && d.status !== 'recusado',
    ).length;

    return { approved, refused, pending };
}

function sectionIcon(kind: string) {
    const map = {
        required: FileStack,
        titles: Award,
        special: Accessibility,
        other: FolderOpen,
    } as const;

    return map[kind as keyof typeof map] ?? FolderOpen;
}
</script>

<template>
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 sm:py-6"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
            >
                <div class="flex gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600"
                    >
                        <FileCheck class="size-5" />
                    </div>
                    <div>
                        <h3
                            class="text-base font-bold tracking-tight text-slate-900 sm:text-lg"
                        >
                            Validação de documentos
                        </h3>
                        <p
                            class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500"
                        >
                            Os anexos estão organizados por tipo: primeiro a
                            documentação obrigatória do edital, depois os
                            <strong class="font-medium text-slate-600"
                                >títulos para pontuação</strong
                            >
                            (agrupados como no processo) e, por fim,
                            complementos como PcD. Use cada bloco como um
                            checklist.
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-wrap items-center gap-2 lg:shrink-0 lg:justify-end"
                >
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200/80"
                    >
                        {{ globalStats.total }}
                        {{ globalStats.total === 1 ? 'arquivo' : 'arquivos' }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200/70"
                    >
                        <CheckCircle2 class="size-3" />
                        {{ globalStats.approved }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200/70"
                    >
                        <XCircle class="size-3" />
                        {{ globalStats.refused }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200/70"
                    >
                        <Clock class="size-3" />
                        {{ globalStats.pending }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Grouped sections -->
        <div class="flex flex-col gap-4 p-4 sm:p-5">
            <details
                v-for="section in sections"
                :key="section.key"
                open
                class="overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-50/40 ring-1 ring-slate-200/60"
            >
                <summary
                    class="flex cursor-pointer list-none items-start gap-3 bg-white/90 px-4 py-3.5 backdrop-blur-sm transition-colors hover:bg-white sm:items-center sm:gap-4 sm:px-5 sm:py-4 [&::-webkit-details-marker]:hidden"
                >
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-slate-200/80"
                        :class="{
                            'text-teal-600': section.kind === 'required',
                            'text-violet-600': section.kind === 'titles',
                            'text-sky-600': section.kind === 'special',
                            'text-slate-600': section.kind === 'other',
                        }"
                    >
                        <component
                            :is="sectionIcon(section.kind)"
                            class="size-4.5"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4
                                class="text-sm font-bold text-slate-900 sm:text-base"
                            >
                                {{ section.title }}
                            </h4>
                            <span
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600"
                            >
                                {{ section.documents.length }}
                                {{
                                    section.documents.length === 1
                                        ? 'item'
                                        : 'itens'
                                }}
                            </span>
                            <span
                                v-if="
                                    sectionStats(section.documents).pending > 0
                                "
                                class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200/70"
                            >
                                {{
                                    sectionStats(section.documents).pending
                                }}
                                a avaliar
                            </span>
                        </div>
                        <p
                            v-if="section.description"
                            class="mt-1 text-xs leading-relaxed text-slate-500 sm:text-sm"
                        >
                            {{ section.description }}
                        </p>
                    </div>

                    <div class="hidden shrink-0 items-center gap-2 sm:flex">
                        <span
                            class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600"
                        >
                            <span
                                class="size-1.5 rounded-full bg-emerald-500"
                            />
                            {{ sectionStats(section.documents).approved }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1 text-[11px] font-medium text-red-600"
                        >
                            <span class="size-1.5 rounded-full bg-red-500" />
                            {{ sectionStats(section.documents).refused }}
                        </span>
                    </div>
                </summary>

                <div
                    class="border-t border-slate-200/60 bg-white px-3 py-3 sm:px-4 sm:py-4"
                >
                    <div class="flex flex-col gap-3">
                        <CandidateDocumentItem
                            v-for="doc in section.documents"
                            :key="doc.id"
                            :document="doc"
                            :application-id="applicationId"
                            :section-kind="section.kind"
                        />
                    </div>
                </div>
            </details>

            <!-- Empty state -->
            <div
                v-if="documents.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-14 text-center"
            >
                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
                >
                    <FileCheck class="size-6" />
                </div>
                <p class="mt-3 text-sm font-semibold text-slate-600">
                    Nenhum documento enviado
                </p>
                <p class="mt-1 max-w-xs text-xs text-slate-400">
                    O candidato ainda não anexou arquivos nesta inscrição.
                </p>
            </div>
        </div>
    </div>
</template>
