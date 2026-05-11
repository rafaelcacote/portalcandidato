<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    ClipboardCheck,
    FileText,
    Upload,
    User,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Fluid from 'primevue/fluid';
import Message from 'primevue/message';
import Select from 'primevue/select';
import Step from 'primevue/step';
import StepList from 'primevue/steplist';
import StepPanel from 'primevue/steppanel';
import StepPanels from 'primevue/steppanels';
import Stepper from 'primevue/stepper';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { home } from '@/routes';
import candidateApplications, {
    index as applicationsIndex,
} from '@/routes/candidate/applications';
import step from '@/routes/candidate/applications/step';
import candidateDocuments from '@/routes/candidate/documents';
import { edit as profileEdit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Minhas inscrições', href: applicationsIndex.url() },
            { title: 'Inscrição', href: '#' },
        ],
    },
});

const props = defineProps<{
    application: {
        id: number;
        status: string;
        numero_protocolo: string | null;
        selection_process_id: number;
        dados_inscricao?: Record<string, unknown> | null;
        finalizada_em?: string | null;
        selection_process?: {
            id: number;
            titulo: string;
            required_documents?: Array<{
                id: number;
                nome: string;
                obrigatorio: boolean;
            }>;
        } | null;
        documents?: Array<{
            id: number;
            process_required_document_id: number;
            nome_arquivo: string;
            tipo_documento?: string | null;
            status: string;
            motivo_recusa?: string | null;
        }>;
    };
}>();

const page = usePage<{ auth: { user: Record<string, unknown> } }>();

const profileUser = computed(() => page.props.auth?.user ?? null);

const activeStep = ref(props.application.status === 'rascunho' ? '1' : '4');

const stepTwoData = (props.application.dados_inscricao?.step2 ?? {}) as Record<string, string>;

const stepTwoForm = useForm({
    payload: {
        formacao: stepTwoData.formacao ?? '',
        experiencia: stepTwoData.experiencia ?? '',
        cursos: stepTwoData.cursos ?? '',
    },
});

const uploadForm = useForm({
    process_required_document_id: '' as string,
    arquivo: null as File | null,
});

const isFinalized = computed(() =>
    ['inscrita', 'em_analise', 'aprovada', 'reprovada'].includes(props.application.status),
);

const statusSeverity: Record<string, 'secondary' | 'success' | 'warn' | 'danger'> = {
    rascunho: 'secondary',
    inscrita: 'success',
    em_analise: 'warn',
    pendencia: 'warn',
    aprovada: 'success',
    reprovada: 'danger',
    cancelada: 'secondary',
};

const statusLabel: Record<string, string> = {
    rascunho: 'Rascunho',
    inscrita: 'Inscrito',
    em_analise: 'Em análise',
    pendencia: 'Pendência',
    aprovada: 'Aprovado',
    reprovada: 'Reprovado',
    cancelada: 'Cancelada',
};

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

const saveFormationStep = (): void => {
    stepTwoForm.post(
        step.store({ application: props.application.id, step: 2 }).url,
        {
            onSuccess: () => {
                activeStep.value = '3';
            },
        },
    );
};

function formatProfileDate(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }
    const s = String(value);
    const d = new Date(s);
    if (Number.isNaN(d.getTime())) {
        return s;
    }
    return d.toLocaleDateString('pt-BR');
}

const profileRows = computed(() => {
    const u = profileUser.value;
    if (!u) {
        return [];
    }
    return [
        { label: 'Nome completo', value: u.name },
        { label: 'E-mail', value: u.email },
        { label: 'CPF', value: u.cpf },
        { label: 'Data de nascimento', value: formatProfileDate(u.data_nascimento) },
        { label: 'Telefone (celular)', value: u.telefone },
        { label: 'Telefone fixo', value: u.telefone_fixo ?? '—' },
        { label: 'Identidade', value: u.identidade },
        { label: 'Órgão emissor', value: u.orgao_emissor },
        { label: 'UF da identidade', value: u.identidade_uf },
        { label: 'Data de emissão da identidade', value: formatProfileDate(u.identidade_data_emissao) },
        { label: 'Naturalidade', value: u.naturalidade },
        { label: 'Nacionalidade', value: u.nacionalidade },
        { label: 'Sexo', value: u.sexo },
        { label: 'Endereço', value: u.endereco },
        { label: 'Número', value: u.endereco_numero },
        { label: 'Bairro', value: u.bairro },
        { label: 'CEP', value: u.cep },
        { label: 'Cidade', value: u.cidade },
        { label: 'UF', value: u.endereco_uf },
        { label: 'País', value: u.pais },
    ];
});

const hasUploadForRequired = (requiredDocumentId: number): boolean =>
    (props.application.documents ?? []).some(
        (d) => d.process_required_document_id === requiredDocumentId,
    );

const submitApplication = (): void => {
    router.post(candidateApplications.submit(props.application.id).url);
};

const selectedFile = ref<File | null>(null);
const selectedDocId = ref<number | null>(null);

const documentUploadOptions = computed(() =>
    (props.application.selection_process?.required_documents ?? []).map((d) => ({
        label: d.nome,
        value: d.id,
    })),
);

const onFileSelect = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    selectedFile.value = target.files?.[0] ?? null;
    uploadForm.arquivo = selectedFile.value;
};

const uploadDocument = (): void => {
    if (selectedDocId.value === null) {
        return;
    }
    uploadForm.process_required_document_id = String(selectedDocId.value);
    uploadForm.arquivo = selectedFile.value;
    uploadForm.transform((data) => ({
        ...data,
        process_required_document_id: Number(data.process_required_document_id),
    }));
    uploadForm.post(candidateDocuments.store(props.application.id).url, {
        onSuccess: () => {
            selectedFile.value = null;
            selectedDocId.value = null;
            uploadForm.reset();
        },
    });
};

function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <div class="p-1">
        <Head title="Detalhe da inscrição" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <!-- Cabeçalho -->
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Detalhe da inscrição"
                    description="Gerencie etapas, documentos e finalização da sua inscrição."
                    :icon="ClipboardCheck"
                />
                <Link :href="applicationsIndex.url()">
                    <Button
                        label="Voltar"
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        outlined
                        size="small"
                    />
                </Link>
            </div>

            <!-- Mensagem de sucesso para inscrição finalizada -->
            <Message v-if="isFinalized" severity="success" :closable="false">
                <template #messageicon>
                    <CheckCircle2 :size="18" />
                </template>
                <div class="flex flex-col gap-0.5">
                    <span class="font-semibold">Inscrição finalizada!</span>
                    <span class="text-sm">
                        Protocolo: <strong>{{ application.numero_protocolo ?? '—' }}</strong>
                        · Finalizada em: {{ formatDate(application.finalizada_em) }}
                    </span>
                </div>
            </Message>

            <!-- Resumo da inscrição -->
            <Card class="rounded-xl shadow-sm">
                <template #content>
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap gap-6">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Processo</p>
                                <p class="mt-0.5 font-semibold text-foreground">
                                    {{ application.selection_process?.titulo ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Protocolo</p>
                                <p class="mt-0.5 font-mono text-sm font-semibold text-foreground">
                                    {{ application.numero_protocolo ?? 'Pendente' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Status</p>
                                <Tag
                                    class="mt-1"
                                    :value="statusLabel[application.status] ?? application.status"
                                    :severity="statusSeverity[application.status] ?? 'secondary'"
                                />
                            </div>
                        </div>
                        <Button
                            v-if="isFinalized"
                            label="Comprovante"
                            icon="pi pi-download"
                            severity="secondary"
                            outlined
                            size="small"
                        />
                    </div>
                </template>
            </Card>

            <!-- Wizard de inscrição -->
            <Card class="rounded-xl shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <ClipboardCheck :size="16" class="text-muted-foreground" />
                        Formulário de inscrição
                    </div>
                </template>
                <template #content>
                    <Stepper v-model:value="activeStep" linear>
                        <StepList>
                            <Step value="1">
                                <template #default="{ active, value }">
                                    <div class="flex items-center gap-2">
                                        <div
                                            :class="[
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                                                active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground',
                                            ]"
                                        >
                                            <User v-if="!active" :size="14" />
                                            <span v-else>{{ value }}</span>
                                        </div>
                                        <span class="hidden font-medium sm:inline">Dados pessoais</span>
                                    </div>
                                </template>
                            </Step>
                            <Step value="2">
                                <template #default="{ active, value }">
                                    <div class="flex items-center gap-2">
                                        <div
                                            :class="[
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                                                active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground',
                                            ]"
                                        >
                                            <span>{{ value }}</span>
                                        </div>
                                        <span class="hidden font-medium sm:inline">Formação e experiência</span>
                                    </div>
                                </template>
                            </Step>
                            <Step value="3">
                                <template #default="{ active, value }">
                                    <div class="flex items-center gap-2">
                                        <div
                                            :class="[
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                                                active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground',
                                            ]"
                                        >
                                            <Upload v-if="!active" :size="14" />
                                            <span v-else>{{ value }}</span>
                                        </div>
                                        <span class="hidden font-medium sm:inline">Documentos</span>
                                    </div>
                                </template>
                            </Step>
                            <Step value="4">
                                <template #default="{ active, value }">
                                    <div class="flex items-center gap-2">
                                        <div
                                            :class="[
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                                                active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground',
                                            ]"
                                        >
                                            <CheckCircle2 v-if="!active" :size="14" />
                                            <span v-else>{{ value }}</span>
                                        </div>
                                        <span class="hidden font-medium sm:inline">Revisão</span>
                                    </div>
                                </template>
                            </Step>
                        </StepList>

                        <StepPanels>
                            <!-- Etapa 1: Dados pessoais -->
                            <StepPanel value="1">
                                <div class="py-4">
                                    <div class="mb-5 flex items-center gap-2">
                                        <User :size="18" class="text-primary" />
                                        <h3 class="text-base font-semibold">Perfil cadastral</h3>
                                    </div>
                                    <Message severity="info" :closable="false" class="mb-4">
                                        Os dados pessoais, documento e endereço vêm do seu cadastro na plataforma.
                                        Para alterá-los, acesse as configurações do perfil.
                                    </Message>
                                    <Fluid>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div
                                                v-for="row in profileRows"
                                                :key="row.label"
                                                class="flex flex-col gap-0.5 rounded-lg border border-border bg-muted/20 px-3 py-2"
                                            >
                                                <span class="text-xs font-medium text-muted-foreground">{{
                                                    row.label
                                                }}</span>
                                                <span class="text-sm font-medium text-foreground">{{
                                                    row.value ?? '—'
                                                }}</span>
                                            </div>
                                        </div>
                                    </Fluid>
                                    <div class="mt-6 flex flex-wrap justify-end gap-2">
                                        <Link :href="profileEdit().url">
                                            <Button
                                                label="Editar perfil"
                                                icon="pi pi-user"
                                                severity="secondary"
                                                outlined
                                                size="small"
                                                type="button"
                                            />
                                        </Link>
                                        <Button
                                            label="Próximo"
                                            icon="pi pi-arrow-right"
                                            icon-pos="right"
                                            size="small"
                                            :disabled="isFinalized"
                                            @click="activeStep = '2'"
                                        />
                                    </div>
                                </div>
                            </StepPanel>

                            <!-- Etapa 2: Formação e experiência -->
                            <StepPanel value="2">
                                <div class="py-4">
                                    <div class="mb-5 flex items-center gap-2">
                                        <FileText :size="18" class="text-primary" />
                                        <h3 class="text-base font-semibold">Formação e experiência</h3>
                                    </div>
                                    <Fluid>
                                        <div class="flex flex-col gap-4">
                                            <div class="flex flex-col gap-1.5">
                                                <label class="text-sm font-medium">Formação acadêmica</label>
                                                <Textarea
                                                    v-model="stepTwoForm.payload.formacao"
                                                    placeholder="Descreva sua formação acadêmica (graduação, pós-graduação, etc.)"
                                                    :rows="3"
                                                    :invalid="!!stepTwoForm.errors['payload.formacao']"
                                                />
                                            </div>
                                            <div class="flex flex-col gap-1.5">
                                                <label class="text-sm font-medium">Experiência profissional</label>
                                                <Textarea
                                                    v-model="stepTwoForm.payload.experiencia"
                                                    placeholder="Descreva sua experiência profissional relevante"
                                                    :rows="4"
                                                />
                                            </div>
                                            <div class="flex flex-col gap-1.5">
                                                <label class="text-sm font-medium">Cursos e certificações</label>
                                                <Textarea
                                                    v-model="stepTwoForm.payload.cursos"
                                                    placeholder="Liste cursos e certificações relevantes"
                                                    :rows="3"
                                                />
                                            </div>
                                        </div>
                                    </Fluid>
                                    <div class="mt-6 flex justify-between gap-2">
                                        <Button
                                            label="Anterior"
                                            icon="pi pi-arrow-left"
                                            severity="secondary"
                                            outlined
                                            size="small"
                                            @click="activeStep = '1'"
                                        />
                                        <Button
                                            label="Próximo"
                                            icon="pi pi-arrow-right"
                                            icon-pos="right"
                                            size="small"
                                            :loading="stepTwoForm.processing"
                                            :disabled="isFinalized"
                                            @click="saveFormationStep"
                                        />
                                    </div>
                                </div>
                            </StepPanel>

                            <!-- Etapa 3: Documentos -->
                            <StepPanel value="3">
                                <div class="py-4">
                                    <div class="mb-5 flex items-center gap-2">
                                        <Upload :size="18" class="text-primary" />
                                        <h3 class="text-base font-semibold">Upload de documentos</h3>
                                    </div>

                                    <!-- Documentos exigidos pelo processo -->
                                    <div
                                        v-if="(application.selection_process?.required_documents ?? []).length"
                                        class="mb-5"
                                    >
                                        <p class="mb-3 text-xs font-medium uppercase text-muted-foreground">
                                            Documentos exigidos pelo processo
                                        </p>
                                        <div class="flex flex-col gap-2">
                                            <div
                                                v-for="reqDoc in application.selection_process?.required_documents ?? []"
                                                :key="reqDoc.id"
                                                class="flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/30 px-4 py-3"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <i class="pi pi-file-o text-muted-foreground" />
                                                    <span class="text-sm font-medium">{{ reqDoc.nome }}</span>
                                                    <Tag
                                                        v-if="reqDoc.obrigatorio"
                                                        value="Obrigatório"
                                                        severity="danger"
                                                        class="text-xs"
                                                    />
                                                </div>
                                                <Tag
                                                    v-if="hasUploadForRequired(reqDoc.id)"
                                                    value="Enviado"
                                                    severity="success"
                                                    icon="pi pi-check"
                                                    class="text-xs"
                                                />
                                                <Tag
                                                    v-else
                                                    value="Pendente"
                                                    severity="warn"
                                                    icon="pi pi-clock"
                                                    class="text-xs"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Formulário de upload -->
                                    <div v-if="!isFinalized" class="rounded-xl border border-dashed border-border p-5">
                                        <p class="mb-4 text-sm font-medium">Enviar novo documento</p>
                                        <Fluid>
                                            <div class="flex flex-col gap-3">
                                                <div class="flex flex-col gap-1.5">
                                                    <label class="text-sm font-medium">Documento obrigatório</label>
                                                    <Select
                                                        v-model="selectedDocId"
                                                        :options="documentUploadOptions"
                                                        option-label="label"
                                                        option-value="value"
                                                        placeholder="Selecione o tipo de documento"
                                                        :invalid="!!uploadForm.errors.process_required_document_id"
                                                        class="w-full"
                                                    />
                                                    <small v-if="uploadForm.errors.process_required_document_id" class="text-red-500">
                                                        {{ uploadForm.errors.process_required_document_id }}
                                                    </small>
                                                </div>
                                                <div class="flex flex-col gap-1.5">
                                                    <label class="text-sm font-medium">Arquivo</label>
                                                    <div class="flex items-center gap-3">
                                                        <label
                                                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-border bg-muted px-4 py-2.5 text-sm font-medium transition-colors hover:bg-muted/80"
                                                        >
                                                            <Upload :size="14" />
                                                            {{ selectedFile ? selectedFile.name : 'Escolher arquivo' }}
                                                            <input type="file" class="sr-only" @change="onFileSelect" />
                                                        </label>
                                                        <Button
                                                            label="Enviar"
                                                            icon="pi pi-upload"
                                                            size="small"
                                                            :loading="uploadForm.processing"
                                                            :disabled="!selectedFile || selectedDocId === null"
                                                            @click="uploadDocument"
                                                        />
                                                    </div>
                                                    <small v-if="uploadForm.errors.arquivo" class="text-red-500">
                                                        {{ uploadForm.errors.arquivo }}
                                                    </small>
                                                </div>
                                            </div>
                                        </Fluid>
                                    </div>

                                    <div class="mt-6 flex justify-between gap-2">
                                        <Button
                                            label="Anterior"
                                            icon="pi pi-arrow-left"
                                            severity="secondary"
                                            outlined
                                            size="small"
                                            @click="activeStep = '2'"
                                        />
                                        <Button
                                            label="Revisar inscrição"
                                            icon="pi pi-arrow-right"
                                            icon-pos="right"
                                            size="small"
                                            @click="activeStep = '4'"
                                        />
                                    </div>
                                </div>
                            </StepPanel>

                            <!-- Etapa 4: Revisão e confirmação -->
                            <StepPanel value="4">
                                <div class="py-4">
                                    <div class="mb-5 flex items-center gap-2">
                                        <CheckCircle2 :size="18" class="text-primary" />
                                        <h3 class="text-base font-semibold">Revisão e confirmação</h3>
                                    </div>

                                    <div class="flex flex-col gap-4">
                                        <!-- Dados pessoais resumo -->
                                        <div class="rounded-xl border border-border p-4">
                                            <div class="mb-3 flex items-center justify-between">
                                                <p class="text-sm font-semibold">Perfil cadastral</p>
                                                <div class="flex gap-1">
                                                    <Link :href="profileEdit().url">
                                                        <Button
                                                            v-if="!isFinalized"
                                                            label="Editar perfil"
                                                            icon="pi pi-user"
                                                            text
                                                            size="small"
                                                        />
                                                    </Link>
                                                    <Button
                                                        v-if="!isFinalized"
                                                        label="Ver etapa"
                                                        icon="pi pi-arrow-left"
                                                        text
                                                        size="small"
                                                        @click="activeStep = '1'"
                                                    />
                                                </div>
                                            </div>
                                            <div class="grid gap-2 text-sm sm:grid-cols-2">
                                                <div
                                                    v-for="row in profileRows.slice(0, 6)"
                                                    :key="row.label"
                                                >
                                                    <span class="text-muted-foreground">{{ row.label }}: </span>
                                                    <span class="font-medium">{{ row.value ?? '—' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Formação resumo -->
                                        <div class="rounded-xl border border-border p-4">
                                            <div class="mb-3 flex items-center justify-between">
                                                <p class="text-sm font-semibold">Formação e experiência</p>
                                                <Button
                                                    v-if="!isFinalized"
                                                    label="Editar"
                                                    icon="pi pi-pencil"
                                                    text
                                                    size="small"
                                                    @click="activeStep = '2'"
                                                />
                                            </div>
                                            <div class="flex flex-col gap-2 text-sm">
                                                <div>
                                                    <span class="text-muted-foreground">Formação: </span>
                                                    <span class="font-medium">{{ stepTwoForm.payload.formacao || '—' }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-muted-foreground">Experiência: </span>
                                                    <span class="font-medium">{{ stepTwoForm.payload.experiencia || '—' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Documentos resumo -->
                                        <div class="rounded-xl border border-border p-4">
                                            <div class="mb-3 flex items-center justify-between">
                                                <p class="text-sm font-semibold">Documentos enviados</p>
                                                <Button
                                                    v-if="!isFinalized"
                                                    label="Gerenciar"
                                                    icon="pi pi-upload"
                                                    text
                                                    size="small"
                                                    @click="activeStep = '3'"
                                                />
                                            </div>
                                            <div v-if="(application.documents ?? []).length === 0" class="text-sm text-muted-foreground">
                                                Nenhum documento enviado.
                                            </div>
                                            <ul v-else class="flex flex-col gap-1.5">
                                                <li
                                                    v-for="doc in application.documents"
                                                    :key="doc.id"
                                                    class="flex items-center gap-2 text-sm"
                                                >
                                                    <i class="pi pi-file-o text-muted-foreground" />
                                                    <span class="flex-1 truncate">{{ doc.nome_arquivo }}</span>
                                                    <Tag
                                                        :value="docStatusLabel[doc.status] ?? doc.status"
                                                        :severity="docStatusSeverity[doc.status] ?? 'secondary'"
                                                        class="text-xs"
                                                    />
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Botão finalizar -->
                                        <div v-if="!isFinalized">
                                            <Message severity="info" :closable="false" class="mb-4">
                                                Ao confirmar, sua inscrição será enviada para análise e você
                                                receberá um e-mail de confirmação com o comprovante.
                                            </Message>
                                            <div class="flex items-center justify-between gap-2">
                                                <Button
                                                    label="Anterior"
                                                    icon="pi pi-arrow-left"
                                                    severity="secondary"
                                                    outlined
                                                    size="small"
                                                    @click="activeStep = '3'"
                                                />
                                                <Button
                                                    label="Finalizar inscrição"
                                                    icon="pi pi-check"
                                                    size="small"
                                                    @click="submitApplication"
                                                />
                                            </div>
                                        </div>

                                        <div v-else class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30">
                                            <div class="flex items-center gap-3">
                                                <CheckCircle2 :size="20" class="text-green-600 dark:text-green-400" />
                                                <div>
                                                    <p class="font-semibold text-green-800 dark:text-green-300">
                                                        Inscrição finalizada
                                                    </p>
                                                    <p class="text-sm text-green-700 dark:text-green-400">
                                                        Protocolo: <strong>{{ application.numero_protocolo }}</strong>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </StepPanel>
                        </StepPanels>
                    </Stepper>
                </template>
            </Card>

            <!-- Gerenciador de documentos -->
            <Card class="rounded-xl shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <FileText :size="16" class="text-muted-foreground" />
                        Meus documentos
                    </div>
                </template>
                <template #content>
                    <div v-if="!(application.documents ?? []).length" class="flex flex-col items-center justify-center gap-3 py-10 text-center">
                        <i class="pi pi-folder-open text-3xl text-muted-foreground" />
                        <p class="text-sm text-muted-foreground">Nenhum documento enviado ainda.</p>
                    </div>
                    <div v-else class="flex flex-col divide-y divide-border">
                        <div
                            v-for="doc in application.documents"
                            :key="doc.id"
                            class="flex flex-wrap items-start justify-between gap-3 py-4 first:pt-0 last:pb-0"
                        >
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted">
                                    <i class="pi pi-file-pdf text-muted-foreground" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-foreground">
                                        {{ doc.nome_arquivo }}
                                    </p>
                                    <p v-if="doc.tipo_documento" class="text-xs text-muted-foreground">
                                        {{ doc.tipo_documento }}
                                    </p>
                                    <div
                                        v-if="doc.status === 'recusado' && doc.motivo_recusa"
                                        class="mt-1 flex items-start gap-1.5 rounded-md bg-red-50 p-2 dark:bg-red-950/30"
                                    >
                                        <AlertTriangle :size="12" class="mt-0.5 shrink-0 text-red-500" />
                                        <p class="text-xs text-red-600 dark:text-red-400">
                                            <strong>Motivo da recusa:</strong> {{ doc.motivo_recusa }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <Tag
                                    :value="docStatusLabel[doc.status] ?? doc.status"
                                    :severity="docStatusSeverity[doc.status] ?? 'secondary'"
                                />
                                <Button
                                    v-if="doc.status === 'recusado' && !isFinalized"
                                    v-tooltip.top="'Reenviar documento'"
                                    icon="pi pi-refresh"
                                    severity="warn"
                                    text
                                    rounded
                                    size="small"
                                    @click="activeStep = '3'"
                                />
                                <Button
                                    v-tooltip.top="'Visualizar'"
                                    icon="pi pi-eye"
                                    text
                                    rounded
                                    size="small"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
