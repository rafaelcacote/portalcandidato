<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Eye, Trash2 } from 'lucide-vue-next';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';
import candidateDocuments, {
    show as showDocument,
} from '@/routes/candidate/documents';

export type RequiredDocRow = {
    id: number;
    nome: string;
    obrigatorio: boolean;
    descricao?: string | null;
};

export type UploadedDocRow = {
    id: number;
    process_required_document_id: number | null;
    candidatura_document_kind?: string | null;
    nome_arquivo: string;
    status: string;
    motivo_recusa?: string | null;
};

const props = defineProps<{
    doc: RequiredDocRow;
    uploadedDoc: UploadedDocRow | null;
    applicationId: number;
    isFinalized?: boolean;
}>();

const emit = defineEmits<{
    pendingChange: [docId: number, label: string, pending: boolean];
}>();

const docStatusSeverity: Record<
    string,
    'secondary' | 'success' | 'warn' | 'danger'
> = {
    enviado: 'success',
    em_analise: 'warn',
    aprovado: 'success',
    recusado: 'danger',
};

const isUploaded = computed(
    () =>
        props.uploadedDoc !== null && props.uploadedDoc.status !== 'recusado',
);

const docStatusLabel: Record<string, string> = {
    enviado: 'Enviado',
    em_analise: 'Em análise',
    aprovado: 'Aprovado',
    recusado: 'Recusado',
};

const fileInputRef = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const removingDocument = ref(false);
const confirm = useConfirm();

const uploadForm = useForm({
    process_required_document_id: props.doc.id,
    arquivo: null as File | null,
});

watch(
    selectedFile,
    (file) => {
        emit('pendingChange', props.doc.id, props.doc.nome, file !== null);
    },
    { immediate: true },
);

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    selectedFile.value = target.files?.[0] ?? null;
}

function submitUpload(): void {
    if (!selectedFile.value) {
        return;
    }

    uploadForm.arquivo = selectedFile.value;
    uploadForm.post(candidateDocuments.store(props.applicationId).url, {
        onSuccess: () => {
            selectedFile.value = null;

            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
        },
    });
}

function documentViewUrl(): string | null {
    if (!props.uploadedDoc) {
        return null;
    }

    return showDocument({
        application: props.applicationId,
        document: props.uploadedDoc.id,
    }).url;
}

function openDocumentView(): void {
    const url = documentViewUrl();

    if (url) {
        window.open(url, '_blank', 'noopener,noreferrer');
    }
}

function confirmRemoveDocument(): void {
    if (props.isFinalized || !props.uploadedDoc) {
        return;
    }

    const uploadedDoc = props.uploadedDoc;

    confirm.require({
        header: 'Excluir documento',
        message: `Deseja excluir o arquivo "${uploadedDoc.nome_arquivo}"? Você poderá enviar um novo arquivo em seguida.`,
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Excluir',
        rejectProps: { outlined: true, icon: 'pi pi-times' },
        acceptProps: { severity: 'danger', icon: 'pi pi-trash' },
        accept: () => {
            removingDocument.value = true;

            router.delete(
                candidateDocuments.destroy({
                    application: props.applicationId,
                    document: uploadedDoc.id,
                }).url,
                {
                    preserveScroll: true,
                    onFinish: () => {
                        removingDocument.value = false;
                    },
                },
            );
        },
    });
}

function cancelSelection(): void {
    selectedFile.value = null;

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}
</script>

<template>
    <div
        :class="[
            'rounded-xl border p-4 transition-colors',
            uploadedDoc?.status === 'recusado'
                ? 'border-red-200 bg-red-50/30 dark:border-red-900 dark:bg-red-950/10'
                : uploadedDoc?.status === 'aprovado'
                  ? 'border-green-200 bg-green-50/30 dark:border-green-900 dark:bg-green-950/10'
                  : uploadedDoc
                    ? 'border-border bg-muted/10'
                    : 'border-border bg-background',
        ]"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-foreground">{{
                        doc.nome
                    }}</span>
                    <Tag
                        v-if="isUploaded"
                        value="Enviado"
                        severity="success"
                        class="text-xs"
                    />
                    <Tag
                        v-else-if="doc.obrigatorio"
                        value="Obrigatório"
                        severity="danger"
                        class="text-xs"
                    />
                    <Tag
                        v-else
                        value="Opcional"
                        severity="secondary"
                        class="text-xs"
                    />
                </div>
                <p
                    v-if="doc.descricao"
                    class="mt-1 text-xs text-muted-foreground"
                >
                    {{ doc.descricao }}
                </p>
            </div>

            <div class="shrink-0">
                <Tag
                    v-if="uploadedDoc"
                    :value="
                        docStatusLabel[uploadedDoc.status] ?? uploadedDoc.status
                    "
                    :severity="
                        docStatusSeverity[uploadedDoc.status] ?? 'secondary'
                    "
                />
                <Tag
                    v-else
                    value="Pendente"
                    severity="warn"
                    icon="pi pi-clock"
                />
            </div>
        </div>

        <div
            v-if="uploadedDoc"
            class="mt-2 flex flex-wrap items-center gap-2"
        >
            <div
                class="flex min-w-0 flex-1 items-center gap-1.5 text-xs text-muted-foreground"
            >
                <i class="pi pi-file-pdf" />
                <span class="truncate">{{ uploadedDoc.nome_arquivo }}</span>
            </div>
            <Button
                label="Visualizar"
                severity="secondary"
                outlined
                size="small"
                @click="openDocumentView"
            >
                <template #icon>
                    <Eye :size="14" aria-hidden="true" />
                </template>
            </Button>
            <Button
                v-if="!isFinalized"
                label="Excluir"
                severity="danger"
                outlined
                size="small"
                :loading="removingDocument"
                @click="confirmRemoveDocument"
            >
                <template #icon>
                    <Trash2 :size="14" aria-hidden="true" />
                </template>
            </Button>
        </div>

        <div
            v-if="
                uploadedDoc?.status === 'recusado' && uploadedDoc.motivo_recusa
            "
            class="mt-2 flex items-start gap-1.5 rounded-md bg-red-50 px-3 py-2 dark:bg-red-950/30"
        >
            <AlertTriangle :size="12" class="mt-0.5 shrink-0 text-red-500" />
            <p class="text-xs text-red-600 dark:text-red-400">
                <strong>Motivo da recusa:</strong>
                {{ uploadedDoc.motivo_recusa }}
            </p>
        </div>

        <div v-if="!isFinalized" class="mt-3">
            <input
                ref="fileInputRef"
                type="file"
                class="sr-only"
                @change="onFileChange"
            />

            <div v-if="selectedFile" class="flex flex-wrap items-center gap-2">
                <span
                    class="flex items-center gap-1.5 rounded-lg bg-primary/5 px-3 py-1.5 text-xs text-foreground"
                >
                    <i class="pi pi-file text-primary" />
                    {{ selectedFile.name }}
                </span>
                <Button
                    label="Enviar"
                    icon="pi pi-upload"
                    size="small"
                    :loading="uploadForm.processing"
                    @click="submitUpload"
                />
                <Button
                    label="Cancelar"
                    severity="secondary"
                    text
                    size="small"
                    :disabled="uploadForm.processing"
                    @click="cancelSelection"
                />
            </div>

            <Button
                v-else-if="!uploadedDoc"
                label="Selecionar arquivo"
                icon="pi pi-upload"
                severity="primary"
                size="small"
                @click="fileInputRef?.click()"
            />

            <small
                v-if="uploadForm.errors.arquivo"
                class="mt-1 block text-xs text-red-500"
            >
                {{ uploadForm.errors.arquivo }}
            </small>
        </div>
    </div>
</template>
