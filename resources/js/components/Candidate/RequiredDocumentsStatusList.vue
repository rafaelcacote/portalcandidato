<script setup lang="ts">
import ProgressBar from 'primevue/progressbar';
import { computed, ref } from 'vue';
import RequiredDocumentCard from '@/components/Candidate/RequiredDocumentCard.vue';
import type {
    RequiredDocRow,
    UploadedDocRow,
} from '@/components/Candidate/RequiredDocumentCard.vue';

const props = defineProps<{
    documents: RequiredDocRow[];
    uploadedDocs: UploadedDocRow[];
    applicationId: number;
    isFinalized?: boolean;
}>();

const emit = defineEmits<{
    pendingUploadsChange: [pending: boolean];
}>();

const pendingUploadByDocId = ref(new Map<number, string>());

function onDocPendingChange(
    docId: number,
    label: string,
    pending: boolean,
): void {
    const next = new Map(pendingUploadByDocId.value);

    if (pending) {
        next.set(docId, label);
    } else {
        next.delete(docId);
    }

    pendingUploadByDocId.value = next;
    emit('pendingUploadsChange', next.size > 0);
}

function hasPendingUploads(): boolean {
    return pendingUploadByDocId.value.size > 0;
}

function getPendingUploadItems(): string[] {
    return [...pendingUploadByDocId.value.values()];
}

defineExpose({
    hasPendingUploads,
    getPendingUploadItems,
});

const requiredDocs = computed(() =>
    props.documents.filter((d) => d.obrigatorio),
);
const optionalDocs = computed(() =>
    props.documents.filter((d) => !d.obrigatorio),
);

function getUploadedDoc(docId: number): UploadedDocRow | null {
    return (
        props.uploadedDocs.find(
            (d) =>
                d.process_required_document_id != null &&
                d.process_required_document_id === docId,
        ) ?? null
    );
}

const uploadedRequiredCount = computed(
    () =>
        requiredDocs.value.filter((d) => {
            const uploaded = getUploadedDoc(d.id);

            return uploaded !== null && uploaded.status !== 'recusado';
        }).length,
);

const requiredProgress = computed(() =>
    requiredDocs.value.length > 0
        ? Math.round(
              (uploadedRequiredCount.value / requiredDocs.value.length) * 100,
          )
        : 100,
);
</script>

<template>
    <div class="flex flex-col gap-5">
        <div
            v-if="documents.length === 0"
            class="py-8 text-center text-sm text-muted-foreground"
        >
            Nenhum documento exigido por este processo.
        </div>

        <template v-else>
            <!-- Obrigatórios -->
            <div v-if="requiredDocs.length > 0">
                <div
                    class="mb-3 flex flex-wrap items-center justify-between gap-2"
                >
                    <p
                        class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Documentos obrigatórios
                    </p>
                    <span class="text-xs text-muted-foreground">
                        {{ uploadedRequiredCount }} de
                        {{ requiredDocs.length }} enviado(s)
                    </span>
                </div>

                <ProgressBar
                    :value="requiredProgress"
                    :show-value="false"
                    class="mb-4 h-1.5"
                    :class="
                        requiredProgress === 100
                            ? '[&_.p-progressbar-value]:bg-green-500'
                            : ''
                    "
                />

                <div class="flex flex-col gap-3">
                    <RequiredDocumentCard
                        v-for="doc in requiredDocs"
                        :key="doc.id"
                        :doc="doc"
                        :uploaded-doc="getUploadedDoc(doc.id)"
                        :application-id="applicationId"
                        :is-finalized="isFinalized"
                        @pending-change="onDocPendingChange"
                    />
                </div>
            </div>

            <!-- Opcionais -->
            <div v-if="optionalDocs.length > 0">
                <p
                    class="mb-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Documentos opcionais
                </p>
                <div class="flex flex-col gap-3">
                    <RequiredDocumentCard
                        v-for="doc in optionalDocs"
                        :key="doc.id"
                        :doc="doc"
                        :uploaded-doc="getUploadedDoc(doc.id)"
                        :application-id="applicationId"
                        :is-finalized="isFinalized"
                        @pending-change="onDocPendingChange"
                    />
                </div>
            </div>
        </template>
    </div>
</template>
