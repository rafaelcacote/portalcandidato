<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, FolderOpen, Upload } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { home } from '@/routes';
import { show as applicationShow } from '@/routes/candidate/applications';
import { store as documentStore } from '@/routes/candidate/documents';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Meus documentos', href: '#' },
        ],
    },
});

const props = defineProps<{
    documents: Array<{
        id: number;
        nome_arquivo: string;
        tipo_documento?: string | null;
        status: string;
        motivo_recusa?: string | null;
        mime?: string | null;
        created_at: string;
        updated_at: string;
        application?: {
            id: number;
            numero_protocolo: string | null;
            selection_process?: { titulo: string } | null;
        } | null;
    }>;
    applications: Array<{
        id: number;
        numero_protocolo: string | null;
        selection_process?: { id: number; titulo: string } | null;
        required_documents?: Array<{
            id: number;
            nome: string;
            obrigatorio: boolean;
        }>;
        can_upload_documents?: boolean;
    }>;
    has_uploadable_applications?: boolean;
}>();

const statusSeverity: Record<
    string,
    'secondary' | 'success' | 'warn' | 'danger'
> = {
    enviado: 'secondary',
    em_analise: 'warn',
    aprovado: 'success',
    recusado: 'danger',
};

const statusLabel: Record<string, string> = {
    enviado: 'Enviado',
    em_analise: 'Em análise',
    aprovado: 'Aprovado',
    recusado: 'Recusado',
};

const statusIcon: Record<string, string> = {
    enviado: 'pi-clock',
    em_analise: 'pi-spin pi-spinner',
    aprovado: 'pi-check-circle',
    recusado: 'pi-times-circle',
};

const filterStatus = ref('');
const filterApp = ref<number | ''>('');

const statusFilterOptions = [
    { label: 'Todos os status', value: '' },
    { label: 'Enviado', value: 'enviado' },
    { label: 'Em análise', value: 'em_analise' },
    { label: 'Aprovado', value: 'aprovado' },
    { label: 'Recusado', value: 'recusado' },
];

const uploadableApplications = computed(() =>
    props.applications.filter((a) => a.can_upload_documents !== false),
);

const canUploadDocuments = computed(
    () =>
        props.has_uploadable_applications ??
        uploadableApplications.value.length > 0,
);

const appFilterOptions = computed(() => [
    { label: 'Todas as inscrições', value: '' },
    ...props.applications.map((a) => ({
        label: a.selection_process?.titulo ?? `Inscrição #${a.id}`,
        value: a.id,
    })),
]);

const filteredDocuments = computed(() =>
    props.documents.filter((doc) => {
        if (filterStatus.value && doc.status !== filterStatus.value) {
            return false;
        }

        if (filterApp.value && doc.application?.id !== filterApp.value) {
            return false;
        }

        return true;
    }),
);

const recusadosCount = computed(
    () => props.documents.filter((d) => d.status === 'recusado').length,
);

// Upload dialog
const showUploadDialog = ref(false);
const selectedAppId = ref<number | null>(null);
const selectedDocId = ref('');
const selectedFile = ref<File | null>(null);

const uploadForm = useForm({
    process_required_document_id: '' as string,
    arquivo: null as File | null,
});

const selectedAppOptions = computed(() =>
    uploadableApplications.value.map((a) => ({
        label: a.selection_process?.titulo ?? `Inscrição #${a.id}`,
        value: a.id,
    })),
);

const selectedAppRequiredDocs = computed(() => {
    if (!selectedAppId.value) {
        return [];
    }

    const app = uploadableApplications.value.find(
        (a) => a.id === selectedAppId.value,
    );

    return (app?.required_documents ?? []).map((d) => ({
        label: d.nome + (d.obrigatorio ? ' *' : ''),
        value: d.id,
    }));
});

const onFileChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    selectedFile.value = target.files?.[0] ?? null;
};

const doUpload = (): void => {
    if (!selectedAppId.value) {
        return;
    }

    uploadForm.process_required_document_id = selectedDocId.value;
    uploadForm.arquivo = selectedFile.value;
    uploadForm.transform((data) => ({
        ...data,
        process_required_document_id: Number(data.process_required_document_id),
    }));
    uploadForm.post(documentStore(selectedAppId.value).url, {
        onSuccess: () => {
            showUploadDialog.value = false;
            selectedFile.value = null;
            selectedDocId.value = '';
            selectedAppId.value = null;
            uploadForm.reset();
        },
    });
};

function applicationAllowsUpload(applicationId: number | null | undefined): boolean {
    if (applicationId == null) {
        return false;
    }

    const application = props.applications.find((a) => a.id === applicationId);

    return application?.can_upload_documents !== false;
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function mimeIcon(mime: string | null | undefined): string {
    if (!mime) {
        return 'pi-file';
    }

    if (mime.includes('pdf')) {
        return 'pi-file-pdf';
    }

    if (mime.includes('image')) {
        return 'pi-image';
    }

    if (mime.includes('word') || mime.includes('document')) {
        return 'pi-file-word';
    }

    return 'pi-file';
}
</script>

<template>
    <div class="p-1">
        <Head title="Meus documentos" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <!-- Cabeçalho -->
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Meus documentos"
                    description="Gerencie todos os documentos enviados nas suas inscrições."
                    :icon="FolderOpen"
                />
                <Button
                    v-if="canUploadDocuments"
                    label="Enviar documento"
                    icon="pi pi-upload"
                    size="small"
                    @click="showUploadDialog = true"
                />
            </div>

            <Message
                v-if="!canUploadDocuments && applications.length > 0"
                severity="warn"
                :closable="false"
            >
                As inscrições para os processos vinculados aos seus documentos
                estão encerradas. Não é possível enviar ou reenviar documentos.
            </Message>

            <!-- Alerta de documentos recusados -->
            <Message
                v-if="recusadosCount > 0"
                severity="error"
                :closable="false"
            >
                <template #messageicon>
                    <AlertTriangle :size="18" />
                </template>
                <div class="flex flex-col gap-0.5">
                    <span class="font-semibold">
                        {{ recusadosCount }} documento{{
                            recusadosCount > 1 ? 's' : ''
                        }}
                        recusado{{ recusadosCount > 1 ? 's' : '' }}
                    </span>
                    <span class="text-sm">
                        Revise os motivos de recusa e reenvie os arquivos
                        {{
                            canUploadDocuments
                                ? 'para continuar com suas inscrições.'
                                : 'nas inscrições com prazo aberto.'
                        }}
                    </span>
                </div>
            </Message>

            <!-- Filtros -->
            <Card class="rounded-xl shadow-sm">
                <template #content>
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex min-w-48 flex-1 flex-col gap-1.5">
                            <label
                                class="text-xs font-medium text-muted-foreground"
                                >Status</label
                            >
                            <Select
                                v-model="filterStatus"
                                :options="statusFilterOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Todos os status"
                                class="w-full"
                            />
                        </div>
                        <div class="flex min-w-48 flex-1 flex-col gap-1.5">
                            <label
                                class="text-xs font-medium text-muted-foreground"
                                >Inscrição</label
                            >
                            <Select
                                v-model="filterApp"
                                :options="appFilterOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Todas as inscrições"
                                class="w-full"
                            />
                        </div>
                        <Button
                            label="Limpar"
                            icon="pi pi-times"
                            severity="secondary"
                            text
                            size="small"
                            @click="
                                filterStatus = '';
                                filterApp = '';
                            "
                        />
                    </div>
                </template>
            </Card>

            <!-- Lista vazia -->
            <div
                v-if="filteredDocuments.length === 0"
                class="flex flex-col items-center justify-center gap-4 rounded-xl border border-border bg-card py-16 text-center"
            >
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-full bg-muted"
                >
                    <i
                        class="pi pi-folder-open text-2xl text-muted-foreground"
                    />
                </div>
                <div>
                    <p class="text-base font-semibold">
                        Nenhum documento encontrado
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            documents.length === 0
                                ? 'Você ainda não enviou nenhum documento.'
                                : 'Nenhum documento corresponde aos filtros selecionados.'
                        }}
                    </p>
                </div>
                <Button
                    v-if="canUploadDocuments"
                    label="Enviar documento"
                    icon="pi pi-upload"
                    size="small"
                    @click="showUploadDialog = true"
                />
            </div>

            <!-- Documentos agrupados por inscrição -->
            <div v-else class="flex flex-col gap-3">
                <Card
                    v-for="doc in filteredDocuments"
                    :key="doc.id"
                    class="rounded-xl shadow-sm"
                >
                    <template #content>
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <!-- Ícone + info do documento -->
                            <div class="flex min-w-0 items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted"
                                >
                                    <i
                                        :class="[
                                            'pi',
                                            mimeIcon(doc.mime),
                                            'text-muted-foreground',
                                        ]"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="truncate font-medium text-foreground"
                                    >
                                        {{ doc.nome_arquivo }}
                                    </p>
                                    <p
                                        v-if="doc.tipo_documento"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ doc.tipo_documento }}
                                    </p>
                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                                    >
                                        <span
                                            v-if="doc.application"
                                            class="flex items-center gap-1"
                                        >
                                            <i class="pi pi-list" />
                                            <Link
                                                :href="
                                                    applicationShow({
                                                        application:
                                                            doc.application.id,
                                                    }).url
                                                "
                                                class="hover:text-primary hover:underline"
                                            >
                                                {{
                                                    doc.application
                                                        .selection_process
                                                        ?.titulo ??
                                                    `Inscrição #${doc.application.id}`
                                                }}
                                            </Link>
                                            <span
                                                v-if="
                                                    doc.application
                                                        .numero_protocolo
                                                "
                                            >
                                                ·
                                                {{
                                                    doc.application
                                                        .numero_protocolo
                                                }}
                                            </span>
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i class="pi pi-calendar" />
                                            Enviado em
                                            {{ formatDate(doc.created_at) }}
                                        </span>
                                    </div>

                                    <!-- Motivo de recusa -->
                                    <div
                                        v-if="
                                            doc.status === 'recusado' &&
                                            doc.motivo_recusa
                                        "
                                        class="mt-2 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-2.5 dark:border-red-900 dark:bg-red-950/30"
                                    >
                                        <AlertTriangle
                                            :size="13"
                                            class="mt-0.5 shrink-0 text-red-500"
                                        />
                                        <div>
                                            <p
                                                class="text-xs font-semibold text-red-700 dark:text-red-400"
                                            >
                                                Documento recusado
                                            </p>
                                            <p
                                                class="text-xs text-red-600 dark:text-red-300"
                                            >
                                                {{ doc.motivo_recusa }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status e ações -->
                            <div class="flex shrink-0 items-center gap-2">
                                <div class="flex items-center gap-1.5">
                                    <i
                                        :class="[
                                            'pi',
                                            statusIcon[doc.status] ?? 'pi-file',
                                            doc.status === 'aprovado'
                                                ? 'text-green-600'
                                                : doc.status === 'recusado'
                                                  ? 'text-red-500'
                                                  : 'text-muted-foreground',
                                        ]"
                                    />
                                    <Tag
                                        :value="
                                            statusLabel[doc.status] ??
                                            doc.status
                                        "
                                        :severity="
                                            statusSeverity[doc.status] ??
                                            'secondary'
                                        "
                                    />
                                </div>

                                <Button
                                    v-tooltip.top="'Visualizar'"
                                    icon="pi pi-eye"
                                    text
                                    rounded
                                    size="small"
                                />
                                <Button
                                    v-if="
                                        doc.status === 'recusado' &&
                                        applicationAllowsUpload(
                                            doc.application?.id,
                                        )
                                    "
                                    v-tooltip.top="'Reenviar documento'"
                                    icon="pi pi-refresh"
                                    severity="warn"
                                    text
                                    rounded
                                    size="small"
                                    @click="
                                        selectedAppId =
                                            doc.application?.id ?? null;
                                        showUploadDialog = true;
                                    "
                                />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>

    <!-- Dialog de upload -->
    <Dialog
        v-model:visible="showUploadDialog"
        modal
        header="Enviar documento"
        :style="{ width: '480px' }"
        :closable="!uploadForm.processing"
    >
        <div class="flex flex-col gap-4 py-2">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium">Inscrição</label>
                <Select
                    v-model="selectedAppId"
                    :options="selectedAppOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Selecione a inscrição"
                    class="w-full"
                />
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium">Tipo de documento</label>
                <Select
                    v-if="selectedAppRequiredDocs.length"
                    v-model="selectedDocId"
                    :options="selectedAppRequiredDocs"
                    option-label="label"
                    option-value="value"
                    placeholder="Selecione o tipo"
                    class="w-full"
                />
                <InputText
                    v-else
                    v-model="selectedDocId"
                    placeholder="ID do documento obrigatório"
                />
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium">Arquivo</label>
                <label
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-border p-6 transition-colors hover:border-primary hover:bg-primary/5"
                >
                    <Upload :size="24" class="text-muted-foreground" />
                    <span class="text-sm text-muted-foreground">
                        {{
                            selectedFile
                                ? selectedFile.name
                                : 'Clique para selecionar ou arraste o arquivo'
                        }}
                    </span>
                    <span class="text-xs text-muted-foreground"
                        >PDF, JPG, PNG (máx. 10MB)</span
                    >
                    <input
                        type="file"
                        class="sr-only"
                        accept=".pdf,.jpg,.jpeg,.png"
                        @change="onFileChange"
                    />
                </label>
                <small v-if="uploadForm.errors.arquivo" class="text-red-500">
                    {{ uploadForm.errors.arquivo }}
                </small>
            </div>

            <Message
                v-if="uploadForm.errors.process_required_document_id"
                severity="error"
                :closable="false"
            >
                {{ uploadForm.errors.process_required_document_id }}
            </Message>
        </div>

        <template #footer>
            <Button
                label="Cancelar"
                severity="secondary"
                text
                :disabled="uploadForm.processing"
                @click="showUploadDialog = false"
            />
            <Button
                label="Enviar documento"
                icon="pi pi-upload"
                :loading="uploadForm.processing"
                :disabled="!selectedAppId || !selectedDocId || !selectedFile"
                @click="doUpload"
            />
        </template>
    </Dialog>
</template>
