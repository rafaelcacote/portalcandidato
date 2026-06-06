<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check, Clock, Eye, FileText, X } from 'lucide-vue-next';
import { computed } from 'vue';
import CandidateStatusBadge from '@/components/Evaluator/CandidateStatusBadge.vue';
import type { EvaluatorApplicationDocument } from '@/components/Evaluator/evaluatorDocumentTypes';
import evaluatorDocuments, {
    view as viewDocument,
} from '@/routes/evaluator/candidates/documents';

const props = defineProps<{
    document: EvaluatorApplicationDocument;
    applicationId: number;
    sectionKind?: 'required' | 'titles' | 'special' | 'other';
}>();

const form = useForm({
    status:
        props.document.status !== 'pendente'
            ? props.document.status
            : 'pendente',
    motivo_recusa: props.document.motivo_recusa ?? '',
});

function documentViewUrl(): string {
    return viewDocument({
        application: props.applicationId,
        applicationDocument: props.document.id,
    }).url;
}

function decide(status: 'aprovado' | 'recusado' | 'pendente'): void {
    form.status = status;
    form.post(
        evaluatorDocuments.decision({
            application: props.applicationId,
            applicationDocument: props.document.id,
        }).url,
        { preserveScroll: true },
    );
}

function titleItem(): EvaluatorApplicationDocument['title_item'] {
    return props.document.title_item ?? null;
}

function requiredDoc(): EvaluatorApplicationDocument['required_document'] {
    return props.document.required_document ?? null;
}

function specialKindLabel(kind: string): string {
    const map: Record<string, string> = {
        pcd_declaracao: 'Declaração — candidato(a) com deficiência',
        pcd_laudo: 'Laudo médico (PcD)',
    };

    return map[kind] ?? `Anexo especial (${kind})`;
}

const heading = computed(() => {
    const d = props.document;
    const r = requiredDoc();

    if (r?.nome) {
        return r.nome;
    }

    const t = titleItem();

    if (t?.title) {
        const code = t.code ? `${String(t.code).trim()} · ` : '';

        return `${code}${t.title}`;
    }

    if (d.candidatura_document_kind) {
        return specialKindLabel(d.candidatura_document_kind);
    }

    return d.nome_arquivo;
});

const subheading = computed(() => {
    const r = requiredDoc();

    if (r?.descricao?.trim()) {
        return r.descricao.trim();
    }

    return null;
});

const contextBadge = computed(() => {
    if (props.sectionKind === 'required' && requiredDoc()) {
        return 'Obrigatório';
    }

    if (props.sectionKind === 'titles') {
        return 'Pontuação de títulos';
    }

    if (props.sectionKind === 'special') {
        return 'Complementar';
    }

    if (props.sectionKind === 'other') {
        return 'Anexo';
    }

    return null;
});

function getFileExtension(filename: string): string {
    const parts = filename.split('.');

    return parts.length > 1 ? parts[parts.length - 1].toUpperCase() : 'DOC';
}

function getFileIconColor(filename: string): string {
    const ext = getFileExtension(filename).toLowerCase();
    const colorMap: Record<string, string> = {
        pdf: 'bg-red-50 text-red-600 ring-red-100',
        doc: 'bg-blue-50 text-blue-600 ring-blue-100',
        docx: 'bg-blue-50 text-blue-600 ring-blue-100',
        xls: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        xlsx: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        jpg: 'bg-violet-50 text-violet-600 ring-violet-100',
        jpeg: 'bg-violet-50 text-violet-600 ring-violet-100',
        png: 'bg-violet-50 text-violet-600 ring-violet-100',
    };

    return colorMap[ext] ?? 'bg-slate-100 text-slate-600 ring-slate-200/80';
}
</script>

<template>
    <article
        class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition-shadow duration-200 hover:border-slate-300/90 hover:shadow-md sm:p-5"
    >
        <!-- Linha 1: identificação + visualizar + status -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-5"
        >
            <div class="flex min-w-0 flex-1 gap-3 sm:gap-4">
                <div
                    :class="[
                        'flex size-11 shrink-0 items-center justify-center rounded-xl text-[10px] font-bold ring-1',
                        getFileIconColor(document.nome_arquivo),
                    ]"
                >
                    <FileText class="size-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            v-if="contextBadge"
                            class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-slate-500 uppercase"
                        >
                            {{ contextBadge }}
                        </span>
                        <div class="sm:hidden">
                            <CandidateStatusBadge
                                :status="document.status"
                                size="sm"
                            />
                        </div>
                    </div>
                    <h5
                        class="mt-1 text-sm leading-snug font-semibold text-slate-900 sm:text-base"
                    >
                        {{ heading }}
                    </h5>
                    <p
                        v-if="subheading"
                        class="mt-1 text-xs leading-relaxed text-slate-500 sm:text-sm"
                    >
                        {{ subheading }}
                    </p>
                    <p
                        class="mt-2 font-mono text-[11px] text-slate-400 sm:text-xs"
                    >
                        <span class="font-sans font-medium text-slate-400"
                            >Arquivo:</span
                        >
                        {{ document.nome_arquivo }}
                    </p>
                </div>
            </div>

            <div
                class="flex shrink-0 flex-row items-center justify-between gap-3 sm:flex-col sm:items-end"
            >
                <div class="hidden sm:block">
                    <CandidateStatusBadge :status="document.status" size="sm" />
                </div>
                <a
                    :href="documentViewUrl()"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition-all hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800 focus-visible:ring-2 focus-visible:ring-teal-400/40 focus-visible:outline-none sm:w-auto sm:min-w-[8.5rem]"
                >
                    <Eye class="size-3.5 shrink-0" />
                    Visualizar
                </a>
            </div>
        </div>

        <!-- Linha 2: observações + ações -->
        <div
            class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 lg:flex-row lg:items-end lg:gap-4"
        >
            <div class="min-w-0 flex-1">
                <label
                    class="mb-1.5 block text-[11px] font-semibold tracking-wide text-slate-400 uppercase"
                >
                    Observações da análise
                </label>
                <textarea
                    v-model="form.motivo_recusa"
                    rows="2"
                    placeholder="Ex.: documento ilegível, divergência com o edital, observações para o candidato…"
                    class="w-full resize-y rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-400/20 focus:outline-none"
                />
            </div>
            <div
                class="flex shrink-0 items-center justify-end gap-2 lg:flex-col lg:items-stretch"
            >
                <span
                    class="mr-auto mb-0 text-[11px] font-medium text-slate-400 lg:hidden"
                    >Decisão</span
                >
                <div class="flex gap-2">
                    <button
                        type="button"
                        title="Aprovar documento"
                        :disabled="form.processing"
                        :class="[
                            'flex size-10 items-center justify-center rounded-xl transition-all duration-150 focus-visible:ring-2 focus-visible:outline-none',
                            document.status === 'aprovado'
                                ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/25'
                                : 'border border-slate-200 bg-white text-slate-400 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 focus-visible:ring-emerald-400/40',
                        ]"
                        @click="decide('aprovado')"
                    >
                        <Check class="size-4" />
                    </button>
                    <button
                        type="button"
                        title="Recusar documento"
                        :disabled="form.processing"
                        :class="[
                            'flex size-10 items-center justify-center rounded-xl transition-all duration-150 focus-visible:ring-2 focus-visible:outline-none',
                            document.status === 'recusado'
                                ? 'bg-red-500 text-white shadow-md shadow-red-500/25'
                                : 'border border-slate-200 bg-white text-slate-400 hover:border-red-300 hover:bg-red-50 hover:text-red-700 focus-visible:ring-red-400/40',
                        ]"
                        @click="decide('recusado')"
                    >
                        <X class="size-4" />
                    </button>
                    <button
                        type="button"
                        title="Marcar como pendente"
                        :disabled="form.processing"
                        :class="[
                            'flex size-10 items-center justify-center rounded-xl transition-all duration-150 focus-visible:ring-2 focus-visible:outline-none',
                            document.status === 'pendente'
                                ? 'bg-amber-400 text-white shadow-md shadow-amber-400/30'
                                : 'border border-slate-200 bg-white text-slate-400 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-800 focus-visible:ring-amber-400/40',
                        ]"
                        @click="decide('pendente')"
                    >
                        <Clock class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </article>
</template>
