<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    CloudUpload,
    FileText,
    Info,
    Paperclip,
    Plus,
    Trash2,
    TrendingUp,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import type { ProcessTitleItemRow } from '@/components/Candidate/processTitleTypes';
import candidateDocuments from '@/routes/candidate/documents';

export type TitleUploadedDoc = {
    id: number;
    nome_arquivo: string;
    status: string;
    motivo_recusa?: string | null;
    process_title_item_id?: number | null;
    quantidade?: number | null;
};

const props = defineProps<{
    item: ProcessTitleItemRow;
    applicationId: number;
    uploadedDocs: TitleUploadedDoc[];
    isFinalized?: boolean;
}>();

const docStatusSeverity: Record<string, 'secondary' | 'success' | 'warn' | 'danger'> = {
    enviado: 'secondary',
    em_analise: 'warn',
    aprovado: 'success',
    recusado: 'danger',
};

const docStatusLabel: Record<string, string> = {
    enviado: 'Enviado',
    em_analise: 'Em análise',
    aprovado: 'Aprovado',
    recusado: 'Recusado',
};

const fileInputRef = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const removingDocId = ref<number | null>(null);

const uploadForm = useForm({
    process_title_item_id: props.item.id,
    arquivo: null as File | null,
});

function formatScore(value: string | number): string {
    const n = typeof value === 'string' ? Number.parseFloat(value) : value;

    if (Number.isNaN(n)) {
        return String(value);
    }

    return n % 1 === 0 ? String(Math.trunc(n)) : n.toFixed(2).replace(/\.?0+$/, '');
}

const acceptedFormatsLabel = computed(() => {
    const formats = props.item.accepted_formats;

    if (!formats?.length) {
        return 'PDF';
    }

    return formats.map((f) => f.toUpperCase()).join(', ');
});

const acceptAttribute = computed(() => {
    const formats = props.item.accepted_formats;

    if (!formats?.length) {
        return '.pdf,.jpg,.jpeg,.png';
    }

    return formats.map((f) => `.${f.replace(/^\./, '')}`).join(',');
});

const validUploadedDocs = computed(() =>
    props.uploadedDocs.filter((d) => d.status !== 'recusado'),
);

const refusedUploadedDocs = computed(() =>
    props.uploadedDocs.filter((d) => d.status === 'recusado'),
);

const hasReachedLimit = computed(() => {
    if (props.item.max_quantity == null) {
        return false;
    }

    return validUploadedDocs.value.length >= props.item.max_quantity;
});

const canUploadMore = computed(() => !props.isFinalized && !hasReachedLimit.value);

const counterLabel = computed(() => {
    const sent = validUploadedDocs.value.length;

    if (props.item.max_quantity == null) {
        return `${sent} comprovante(s) enviado(s)`;
    }

    return `${sent} de ${props.item.max_quantity} comprovante(s) enviado(s)`;
});

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    selectedFile.value = file;
    uploadForm.clearErrors();
}

function submitUpload(): void {
    if (!selectedFile.value) {
        return;
    }

    uploadForm.arquivo = selectedFile.value;

    uploadForm.post(candidateDocuments.store(props.applicationId).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            selectedFile.value = null;

            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
        },
    });
}

function cancelSelection(): void {
    selectedFile.value = null;
    uploadForm.clearErrors();

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function removeDocument(doc: TitleUploadedDoc): void {
    if (props.isFinalized) {
        return;
    }

    if (typeof window !== 'undefined') {
        const ok = window.confirm(
            `Remover o comprovante "${doc.nome_arquivo}"? Esta ação não pode ser desfeita.`,
        );

        if (!ok) {
            return;
        }
    }

    removingDocId.value = doc.id;

    router.delete(
        candidateDocuments.destroy({ application: props.applicationId, document: doc.id }).url,
        {
            preserveScroll: true,
            onFinish: () => {
                removingDocId.value = null;
            },
        },
    );
}

const cardToneClass = computed(() => {
    if (refusedUploadedDocs.value.length > 0 && validUploadedDocs.value.length === 0) {
        return 'border-red-200 bg-red-50/40 dark:border-red-900/60 dark:bg-red-950/15';
    }

    if (validUploadedDocs.value.some((d) => d.status === 'aprovado')) {
        return 'border-emerald-200 bg-emerald-50/40 dark:border-emerald-900/60 dark:bg-emerald-950/15';
    }

    if (validUploadedDocs.value.length > 0) {
        return 'border-border/80 bg-card';
    }

    return 'border-border/70 bg-card/60 dark:bg-card/40';
});

/**
 * Pontuação prévia estimada para este item com base nos comprovantes enviados.
 * Segue a mesma regra do backend: score_per_unit × quantidade (limitada por max_quantity),
 * somada para todos os comprovantes válidos.
 * É somente uma ESTIMATIVA — a pontuação final é definida pelo avaliador.
 */
const previewScore = computed((): number | null => {
    if (validUploadedDocs.value.length === 0) {
        return null;
    }

    const perUnit = Number(props.item.score_per_unit);
    if (!Number.isFinite(perUnit) || perUnit <= 0) {
        return null;
    }

    let total = 0;
    for (const doc of validUploadedDocs.value) {
        let qty = Math.max(1, Number(doc.quantidade ?? 1));
        if (props.item.max_quantity != null) {
            qty = Math.min(qty, props.item.max_quantity);
        }
        total += perUnit * qty;
    }

    return Math.round(total * 100) / 100;
});
</script>

<template>
    <article
        :class="[
            'flex flex-col gap-3 rounded-2xl border p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-colors',
            cardToneClass,
        ]"
    >
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">
                    {{ item.code }}
                </p>
                <h5 class="mt-0.5 text-[14.5px] font-semibold leading-snug text-foreground">
                    {{ item.title }}
                </h5>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <Tag
                        :value="`${formatScore(item.score_per_unit)} pts / ${item.score_unit}`"
                        severity="secondary"
                        class="text-[11px]"
                    />
                    <Tag
                        v-if="item.requires_attachment"
                        value="Comprovante obrigatório"
                        severity="warn"
                        icon="pi pi-paperclip"
                        class="text-[11px]"
                    />
                    <Tag
                        v-else
                        value="Comprovante opcional"
                        severity="secondary"
                        class="text-[11px]"
                    />
                    <span
                        v-if="item.max_quantity != null"
                        class="inline-flex items-center rounded-md bg-muted/70 px-1.5 py-0.5 text-[11px] text-muted-foreground"
                    >
                        Até {{ item.max_quantity }} comprovante(s)
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center rounded-md bg-muted/70 px-1.5 py-0.5 text-[11px] text-muted-foreground"
                    >
                        Sem limite de comprovantes
                    </span>
                    <span
                        v-if="item.period_rule"
                        class="inline-flex items-center rounded-md bg-muted/70 px-1.5 py-0.5 text-[11px] text-muted-foreground"
                    >
                        Regra: {{ item.period_rule }}
                    </span>
                </div>
            </div>

            <div class="shrink-0">
                <Tag
                    v-if="validUploadedDocs.length > 0"
                    :value="counterLabel"
                    severity="info"
                    class="text-[11px]"
                />
                <Tag
                    v-else
                    value="Sem envio"
                    severity="secondary"
                    icon="pi pi-circle"
                    class="text-[11px]"
                />
            </div>
        </header>

        <!-- Pontuação prévia estimada -->
        <div
            v-if="previewScore !== null"
            class="flex items-start gap-2 rounded-xl border border-amber-200/80 bg-amber-50/60 px-3 py-2.5 dark:border-amber-800/40 dark:bg-amber-950/20"
        >
            <TrendingUp
                :size="14"
                class="mt-0.5 shrink-0 text-amber-600 dark:text-amber-400"
                aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
                <p class="text-[12px] font-semibold text-amber-800 dark:text-amber-300">
                    Pontuação prévia estimada:
                    <span class="tabular-nums">{{ formatScore(previewScore) }} pts</span>
                </p>
                <p class="mt-0.5 flex items-center gap-1 text-[11px] text-amber-700/80 dark:text-amber-400/80">
                    <Info :size="11" aria-hidden="true" />
                    Valor estimado com base nos comprovantes enviados. A pontuação final é definida pelo avaliador.
                </p>
            </div>
        </div>

        <p
            v-if="item.candidate_instructions"
            class="rounded-xl bg-primary/5 px-3 py-2 text-[12px] leading-relaxed text-foreground/90"
        >
            {{ item.candidate_instructions }}
        </p>

        <ul v-if="validUploadedDocs.length > 0" class="flex flex-col gap-2">
            <li
                v-for="doc in validUploadedDocs"
                :key="doc.id"
                class="flex items-center gap-2 rounded-xl border border-border/60 bg-background/60 px-3 py-2 dark:bg-background/40"
            >
                <FileText :size="14" class="shrink-0 text-primary" aria-hidden="true" />
                <p
                    class="min-w-0 flex-1 truncate text-[12.5px] font-medium text-foreground"
                    :title="doc.nome_arquivo"
                >
                    {{ doc.nome_arquivo }}
                </p>
                <Tag
                    :value="docStatusLabel[doc.status] ?? doc.status"
                    :severity="docStatusSeverity[doc.status] ?? 'secondary'"
                    class="text-[10px]"
                />
                <CheckCircle2
                    v-if="doc.status === 'aprovado'"
                    :size="14"
                    class="shrink-0 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
                <Button
                    v-if="!isFinalized"
                    type="button"
                    severity="danger"
                    text
                    rounded
                    size="small"
                    aria-label="Remover comprovante"
                    :loading="removingDocId === doc.id"
                    @click="removeDocument(doc)"
                >
                    <template #default>
                        <Trash2 :size="14" aria-hidden="true" />
                    </template>
                </Button>
            </li>
        </ul>

        <div
            v-for="refused in refusedUploadedDocs"
            :key="`refused-${refused.id}`"
            class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 dark:border-red-900/50 dark:bg-red-950/30"
        >
            <AlertTriangle
                :size="14"
                class="mt-0.5 shrink-0 text-red-600 dark:text-red-400"
                aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
                <p class="truncate text-[12px] font-semibold text-red-700 dark:text-red-300">
                    {{ refused.nome_arquivo }} (recusado)
                </p>
                <p
                    v-if="refused.motivo_recusa"
                    class="mt-0.5 text-[11.5px] leading-relaxed text-red-700 dark:text-red-300"
                >
                    {{ refused.motivo_recusa }}
                </p>
            </div>
            <Button
                v-if="!isFinalized"
                type="button"
                severity="danger"
                text
                rounded
                size="small"
                aria-label="Remover comprovante recusado"
                :loading="removingDocId === refused.id"
                @click="removeDocument(refused)"
            >
                <template #default>
                    <Trash2 :size="14" aria-hidden="true" />
                </template>
            </Button>
        </div>

        <div v-if="!isFinalized" class="flex flex-col gap-2">
            <p class="text-[11px] text-muted-foreground">
                Formatos aceitos: <span class="font-medium text-foreground">{{ acceptedFormatsLabel }}</span>
                · até {{ item.max_file_size_mb }} MB
            </p>

            <input
                ref="fileInputRef"
                type="file"
                class="sr-only"
                :accept="acceptAttribute"
                :disabled="!canUploadMore"
                @change="onFileChange"
            />

            <div v-if="selectedFile" class="flex flex-wrap items-center gap-2">
                <span
                    class="flex max-w-full items-center gap-1.5 truncate rounded-lg bg-primary/10 px-3 py-1.5 text-[12.5px] font-medium text-foreground"
                >
                    <Paperclip :size="13" class="shrink-0 text-primary" aria-hidden="true" />
                    <span class="truncate">{{ selectedFile.name }}</span>
                </span>
                <Button
                    label="Enviar"
                    icon="pi pi-cloud-upload"
                    size="small"
                    :loading="uploadForm.processing"
                    @click="submitUpload"
                />
                <Button
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    size="small"
                    aria-label="Cancelar seleção"
                    :disabled="uploadForm.processing"
                    @click="cancelSelection"
                />
            </div>

            <div v-else class="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    :severity="validUploadedDocs.length > 0 ? 'secondary' : 'primary'"
                    :outlined="validUploadedDocs.length > 0"
                    size="small"
                    :disabled="!canUploadMore"
                    @click="fileInputRef?.click()"
                >
                    <template #default>
                        <span class="flex items-center gap-1.5">
                            <Plus v-if="validUploadedDocs.length > 0" :size="14" aria-hidden="true" />
                            <CloudUpload v-else :size="14" aria-hidden="true" />
                            {{ validUploadedDocs.length > 0 ? 'Adicionar comprovante' : 'Enviar comprovante' }}
                        </span>
                    </template>
                </Button>
                <span
                    v-if="hasReachedLimit"
                    class="text-[11.5px] font-medium text-muted-foreground"
                >
                    Limite de envios atingido para este item.
                </span>
            </div>

            <small
                v-if="uploadForm.errors.arquivo"
                class="block text-[11.5px] font-medium text-red-600 dark:text-red-400"
            >
                {{ uploadForm.errors.arquivo }}
            </small>
            <small
                v-if="uploadForm.errors.process_title_item_id"
                class="block text-[11.5px] font-medium text-red-600 dark:text-red-400"
            >
                {{ uploadForm.errors.process_title_item_id }}
            </small>
        </div>
    </article>
</template>
