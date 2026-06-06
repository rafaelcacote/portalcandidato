<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Accessibility,
    AlertTriangle,
    Award,
    CheckCircle2,
    ClipboardCheck,
    FileText,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import StepPanel from 'primevue/steppanel';
import Tag from 'primevue/tag';
import { computed, nextTick, ref } from 'vue';
import ApplicationModernStepper from '@/components/Candidate/ApplicationModernStepper.vue';
import ApplicationProfessionalDocuments from '@/components/Candidate/ApplicationProfessionalDocuments.vue';
import type {
    AppealRow,
    AppealStageRow,
    ProfessionalDocumentRow,
} from '@/components/Candidate/ApplicationProfessionalDocuments.vue';
import ApplicationProgressCards from '@/components/Candidate/ApplicationProgressCards.vue';
import CandidateApplicationHeader from '@/components/Candidate/CandidateApplicationHeader.vue';
import CandidatePersonalDataPanel from '@/components/Candidate/CandidatePersonalDataPanel.vue';
import CandidateTitleGroupsUpload from '@/components/Candidate/CandidateTitleGroupsUpload.vue';
import CandidaturaSpecialDocumentUpload from '@/components/Candidate/CandidaturaSpecialDocumentUpload.vue';
import type { ProcessTitleGroupRow } from '@/components/Candidate/processTitleTypes';
import type { CandidateProfileUser } from '@/components/Candidate/profileTypes';
import RequiredDocumentsStatusList from '@/components/Candidate/RequiredDocumentsStatusList.vue';
import { home } from '@/routes';
import candidateApplications, {
    index as applicationsIndex,
} from '@/routes/candidate/applications';
import step from '@/routes/candidate/applications/step';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Minhas inscrições', href: applicationsIndex.url() },
            { title: 'Inscrição', href: '#' },
        ],
    },
});

type ApplicationDocumentRow = {
    id: number;
    process_required_document_id: number | null;
    process_title_item_id?: number | null;
    candidatura_document_kind?: string | null;
    nome_arquivo: string;
    tipo_documento?: string | null;
    status: string;
    motivo_recusa?: string | null;
    quantidade?: number | null;
    required_document?: { nome: string; descricao?: string | null } | null;
    title_item?: { code?: string | null; title: string } | null;
};

const personalDataPanelRef = ref<InstanceType<
    typeof CandidatePersonalDataPanel
> | null>(null);

const props = withDefaults(
    defineProps<{
        ufs?: string[];
        mustVerifyEmail?: boolean;
        application: {
            id: number;
            status: string;
            numero_protocolo: string | null;
            selection_process_id: number;
            dados_inscricao?: Record<string, unknown> | null;
            finalizada_em?: string | null;
            updated_at?: string | null;
            selection_process?: {
                id: number;
                titulo: string;
                tipo_programa?: string | null;
                inscricao_fim_em?: string | null;
                inscricao_inicio_em?: string | null;
                edital_download_url?: string | null;
                required_documents?: Array<{
                    id: number;
                    nome: string;
                    obrigatorio: boolean;
                    descricao?: string | null;
                }>;
                title_groups?: ProcessTitleGroupRow[];
            } | null;
            documents?: ApplicationDocumentRow[];
        };
        professionalDocuments?: ProfessionalDocumentRow[];
        appealStages?: AppealStageRow[];
        appeals?: AppealRow[];
        hasOpenRecursoWindow?: boolean;
    }>(),
    {
        ufs: () => [],
        mustVerifyEmail: false,
        professionalDocuments: () => [],
        appealStages: () => [],
        appeals: () => [],
        hasOpenRecursoWindow: false,
    },
);

const page = usePage<{ auth: { user: CandidateProfileUser | null } }>();

const profileUser = computed<CandidateProfileUser | null>(
    () => page.props.auth?.user ?? null,
);

async function openProfileEditOnStep2(): Promise<void> {
    activeStep.value = '2';
    await nextTick();
    personalDataPanelRef.value?.startEditing();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resolveInitialActiveStep(): string {
    if (props.application.status !== 'rascunho') {
        return '5';
    }

    const stepParam = new URLSearchParams(window.location.search).get('step');

    if (stepParam !== null && /^[1-5]$/.test(stepParam)) {
        return stepParam;
    }

    return '1';
}

const activeStep = ref(resolveInitialActiveStep());

const step1Data = (props.application.dados_inscricao?.step_1 ?? {}) as {
    concorre_vagas_pcd?: boolean;
};

const stepOneForm = useForm({
    payload: {
        concorre_vagas_pcd: step1Data.concorre_vagas_pcd ?? false,
    },
});

const confirmDeclaration = ref(false);

const isFinalized = computed(() =>
    ['inscrita', 'em_analise', 'aprovada', 'reprovada'].includes(
        props.application.status,
    ),
);

const concorrePcdAtivo = computed(() => {
    const s1 = props.application.dados_inscricao?.step_1 as
        | { concorre_vagas_pcd?: boolean }
        | undefined;

    return s1?.concorre_vagas_pcd === true;
});

const pcdDocsComplete = computed(() => {
    if (!concorrePcdAtivo.value) {
        return true;
    }

    const docs = props.application.documents ?? [];
    const active = docs.filter((d) => d.status !== 'recusado');
    const hasDecl = active.some(
        (d) => d.candidatura_document_kind === 'pcd_declaracao',
    );
    const hasLaudo = active.some(
        (d) => d.candidatura_document_kind === 'pcd_laudo',
    );

    return hasDecl && hasLaudo;
});

const pendingPcdDocLabels = computed((): string[] => {
    if (!concorrePcdAtivo.value || pcdDocsComplete.value) {
        return [];
    }

    const docs = props.application.documents ?? [];
    const active = docs.filter((d) => d.status !== 'recusado');
    const missing: string[] = [];

    if (!active.some((d) => d.candidatura_document_kind === 'pcd_declaracao')) {
        missing.push('Declaração de Pessoa com Deficiência (modelo do edital)');
    }

    if (!active.some((d) => d.candidatura_document_kind === 'pcd_laudo')) {
        missing.push(
            'Laudo médico / parecer multiprofissional ou Carteira PcD',
        );
    }

    return missing;
});

const pendingRequiredDocs = computed(() => {
    const requiredDocs =
        props.application.selection_process?.required_documents?.filter(
            (d) => d.obrigatorio,
        ) ?? [];
    const uploadedIds = (props.application.documents ?? [])
        .filter(
            (d) =>
                d.process_required_document_id != null &&
                d.status !== 'recusado',
        )
        .map((d) => d.process_required_document_id as number);

    return requiredDocs.filter((d) => !uploadedIds.includes(d.id));
});

const canSubmit = computed(
    () =>
        pendingRequiredDocs.value.length === 0 &&
        pcdDocsComplete.value &&
        confirmDeclaration.value,
);

const step1Committed = computed(
    () =>
        props.application.dados_inscricao != null &&
        Object.prototype.hasOwnProperty.call(
            props.application.dados_inscricao,
            'step_1',
        ),
);

const canLeavePcdStep = computed(() => {
    if (isFinalized.value) {
        return false;
    }

    if (!step1Committed.value) {
        return false;
    }

    return pcdDocsComplete.value;
});

const aguardandoSalvarOpcaoPcd = computed(
    () =>
        stepOneForm.payload.concorre_vagas_pcd === true &&
        !concorrePcdAtivo.value,
);

const savePcdStep = (): void => {
    stepOneForm.post(
        step.store({ application: props.application.id, step: 1 }).url,
        {
            preserveScroll: true,
            onSuccess: (page) => {
                const app = page.props.application as typeof props.application;
                const v = (
                    app.dados_inscricao?.step_1 as
                        | { concorre_vagas_pcd?: boolean }
                        | undefined
                )?.concorre_vagas_pcd;

                if (typeof v === 'boolean') {
                    stepOneForm.payload.concorre_vagas_pcd = v;
                }
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

function profileText(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
}

/** Primeiros campos exibidos no resumo da etapa 5 (ordem estável para conferência rápida). */
const profileReviewSummary = computed(() => {
    const u = profileUser.value;

    if (!u) {
        return [];
    }

    return [
        { label: 'Nome completo', value: profileText(u.name) },
        { label: 'E-mail', value: profileText(u.email) },
        { label: 'CPF', value: profileText(u.cpf) },
        {
            label: 'Data de nascimento',
            value: formatProfileDate(u.data_nascimento),
        },
        { label: 'Telefone (celular)', value: profileText(u.telefone) },
        { label: 'Telefone fixo', value: profileText(u.telefone_fixo) },
    ];
});

const pcdDeclaracaoDoc = computed(
    () =>
        (props.application.documents ?? []).find(
            (d) => d.candidatura_document_kind === 'pcd_declaracao',
        ) ?? null,
);

const pcdLaudoDoc = computed(
    () =>
        (props.application.documents ?? []).find(
            (d) => d.candidatura_document_kind === 'pcd_laudo',
        ) ?? null,
);

function documentLinkedTitle(doc: ApplicationDocumentRow): string | null {
    if (doc.required_document?.nome) {
        return doc.required_document.nome;
    }

    if (doc.title_item?.title) {
        const code = doc.title_item.code ? `${doc.title_item.code} – ` : '';

        return `${code}${doc.title_item.title}`;
    }

    return null;
}

function documentRowLabel(doc: ApplicationDocumentRow): string {
    if (doc.candidatura_document_kind === 'pcd_declaracao') {
        return 'Declaração PcD (ações afirmativas)';
    }

    if (doc.candidatura_document_kind === 'pcd_laudo') {
        return 'Laudo médico ou carteira PcD';
    }

    return documentLinkedTitle(doc) ?? doc.nome_arquivo;
}

const submitApplication = (): void => {
    router.post(candidateApplications.submit(props.application.id).url);
};

const programTypeLabel = computed((): string | null => {
    const raw = props.application.selection_process?.tipo_programa;

    if (!raw) {
        return null;
    }

    const map: Record<string, string> = {
        mestrado: 'Mestrado',
        doutorado: 'Doutorado',
    };

    return map[raw] ?? raw;
});

const totalTitleItems = computed((): number => {
    let count = 0;

    for (const g of props.application.selection_process?.title_groups ?? []) {
        count += g.items.length;
    }

    return count;
});

const uploadedTitleFilesCount = computed((): number => {
    const docs = props.application.documents ?? [];

    return docs.filter(
        (d) =>
            typeof d.process_title_item_id === 'number' &&
            d.process_title_item_id !== null &&
            d.status !== 'recusado',
    ).length;
});

const itemsWithUploadedTitleCount = computed((): number => {
    const docs = props.application.documents ?? [];
    const ids = new Set<number>();

    for (const d of docs) {
        if (
            typeof d.process_title_item_id === 'number' &&
            d.process_title_item_id !== null &&
            d.status !== 'recusado'
        ) {
            ids.add(d.process_title_item_id);
        }
    }

    return ids.size;
});

const enrollmentMilestones = computed((): boolean[] => {
    const cur = Number.parseInt(activeStep.value, 10) || 1;
    const done1 = step1Committed.value && pcdDocsComplete.value;
    const done2 = cur >= 3;
    const done3 = cur >= 4;
    const done4 = pendingRequiredDocs.value.length === 0;
    const done5 = props.application.status !== 'rascunho';

    return [done1, done2, done3, done4, done5];
});

const completedMilestoneCount = computed(
    () => enrollmentMilestones.value.filter(Boolean).length,
);

const enrollmentProgressPercent = computed(() =>
    Math.round((completedMilestoneCount.value / 5) * 100),
);

const hasStartedEnrollment = computed(
    () =>
        props.application.status === 'rascunho' &&
        completedMilestoneCount.value > 0,
);

const stepNavigatorStates = computed(
    (): Record<string, 'done' | 'current' | 'upcoming'> => {
        const cur = Number.parseInt(activeStep.value, 10) || 1;
        const m = enrollmentMilestones.value;
        const out: Record<string, 'done' | 'current' | 'upcoming'> = {};

        for (let i = 1; i <= 5; i++) {
            const key = String(i);

            if (i === cur) {
                out[key] = 'current';
            } else if (i < cur || m[i - 1]) {
                out[key] = 'done';
            } else {
                out[key] = 'upcoming';
            }
        }

        return out;
    },
);

const documentsUploadedCount = computed(
    () =>
        (props.application.documents ?? []).filter(
            (d) =>
                d.status !== 'recusado' &&
                (d.process_required_document_id != null ||
                    Boolean(d.candidatura_document_kind)),
        ).length,
);

const pendingItemsCount = computed(
    () => pendingRequiredDocs.value.length + pendingPcdDocLabels.value.length,
);

const deadlineDisplay = computed((): string => {
    const raw = props.application.selection_process?.inscricao_fim_em;

    if (!raw) {
        return 'Consulte o edital';
    }

    const d = new Date(raw);

    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return d.toLocaleString('pt-BR', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const deadlineHint = computed((): string | undefined => {
    if (!props.application.selection_process?.inscricao_fim_em) {
        return undefined;
    }

    return 'Encerramento das inscrições conforme calendário publicado.';
});

const isSavingEnrollment = computed(() => stepOneForm.processing);

const showFinalizeEnrollmentReminder = computed(
    () => props.application.status === 'rascunho' && hasStartedEnrollment.value,
);

function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) {
        return '—';
    }

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

        <div
            class="mx-auto flex w-full max-w-[1820px] flex-col gap-6 pb-8 sm:gap-8 sm:pb-10"
        >
            <CandidateApplicationHeader
                :process-title="
                    application.selection_process?.titulo ?? 'Processo seletivo'
                "
                :process-type-label="programTypeLabel"
                :status="application.status"
                :has-started="hasStartedEnrollment"
                :protocol="application.numero_protocolo"
                :deadline-text="deadlineDisplay"
                :deadline-hint="deadlineHint"
                :completed-steps="completedMilestoneCount"
                :total-steps="5"
                :edital-url="
                    application.selection_process?.edital_download_url ?? null
                "
                :back-href="applicationsIndex.url()"
                :updated-at="application.updated_at ?? null"
                :is-saving="isSavingEnrollment"
                :is-finalized="isFinalized"
            />

            <div
                v-if="isFinalized"
                class="flex gap-3 rounded-2xl border border-emerald-200/60 bg-emerald-50/90 px-4 py-3.5 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/30"
                role="status"
            >
                <CheckCircle2
                    :size="20"
                    class="mt-0.5 shrink-0 text-emerald-600 dark:text-emerald-400"
                />
                <div class="flex flex-col gap-0.5">
                    <span
                        class="font-semibold text-emerald-950 dark:text-emerald-100"
                        >Inscrição finalizada!</span
                    >
                    <span
                        class="text-sm text-emerald-900/90 dark:text-emerald-200/90"
                    >
                        Protocolo:
                        <strong>{{
                            application.numero_protocolo ?? '—'
                        }}</strong>
                        · Finalizada em:
                        {{ formatDate(application.finalizada_em) }}
                    </span>
                </div>
            </div>

            <Message
                v-else-if="showFinalizeEnrollmentReminder"
                severity="warn"
                :closable="false"
                icon="pi pi-exclamation-triangle"
            >
                Sua inscrição ainda não foi enviada. Conclua todas as etapas e
                finalize na etapa
                <strong>Revisar Inscrição</strong>
                para enviar ao processo seletivo.
            </Message>

            <ApplicationProgressCards
                :progress-percent="enrollmentProgressPercent"
                :documents-count="documentsUploadedCount"
                :titles-count="uploadedTitleFilesCount"
                :pending-count="pendingItemsCount"
            />

            <Card
                class="rounded-2xl border border-border/60 shadow-sm dark:border-border/40"
                :pt="{
                    body: { class: 'p-4 sm:p-6 lg:p-8' },
                    title: { class: 'px-4 pt-5 sm:px-6 lg:px-8' },
                }"
            >
                <template #title>
                    <div class="flex items-center gap-2">
                        <ClipboardCheck
                            :size="16"
                            class="text-muted-foreground"
                        />
                        Formulário de inscrição
                    </div>
                </template>
                <template #content>
                    <ApplicationModernStepper
                        v-model="activeStep"
                        :step-states="stepNavigatorStates"
                        :is-read-only="isFinalized"
                    >
                        <!-- Etapa 1: PcD -->
                        <StepPanel value="1">
                            <div class="py-4">
                                <div class="mb-5 flex items-center gap-2">
                                    <Accessibility
                                        :size="18"
                                        class="text-primary"
                                    />
                                    <h3 class="text-base font-semibold">
                                        Ações afirmativas — PcD
                                    </h3>
                                </div>
                                <p class="mb-4 text-sm text-muted-foreground">
                                    Esta etapa é obrigatória para registrar se
                                    você concorre às vagas reservadas às pessoas
                                    com deficiência, conforme o edital.
                                </p>

                                <div
                                    class="rounded-xl border border-border bg-muted/15 p-4"
                                >
                                    <p
                                        class="text-sm font-medium text-foreground"
                                    >
                                        Concorrer às vagas destinadas às ações
                                        afirmativas para Pessoa com Deficiência
                                        (PcD)?
                                    </p>
                                    <div class="mt-4 flex flex-wrap gap-6">
                                        <label
                                            class="flex cursor-pointer items-center gap-2 text-sm"
                                        >
                                            <input
                                                v-model="
                                                    stepOneForm.payload
                                                        .concorre_vagas_pcd
                                                "
                                                type="radio"
                                                class="h-4 w-4 accent-primary"
                                                :value="false"
                                                :disabled="isFinalized"
                                            />
                                            Não
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 text-sm"
                                        >
                                            <input
                                                v-model="
                                                    stepOneForm.payload
                                                        .concorre_vagas_pcd
                                                "
                                                type="radio"
                                                class="h-4 w-4 accent-primary"
                                                :value="true"
                                                :disabled="isFinalized"
                                            />
                                            Sim
                                        </label>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <Button
                                            label="Salvar minha opção"
                                            icon="pi pi-save"
                                            size="small"
                                            :loading="stepOneForm.processing"
                                            :disabled="isFinalized"
                                            @click="savePcdStep"
                                        />
                                    </div>
                                    <small
                                        v-if="
                                            stepOneForm.errors[
                                                'payload.concorre_vagas_pcd'
                                            ]
                                        "
                                        class="mt-2 block text-red-500"
                                    >
                                        {{
                                            stepOneForm.errors[
                                                'payload.concorre_vagas_pcd'
                                            ]
                                        }}
                                    </small>
                                </div>

                                <Message
                                    v-if="aguardandoSalvarOpcaoPcd"
                                    severity="warn"
                                    :closable="false"
                                    class="mt-4"
                                >
                                    Clique em
                                    <strong>Salvar minha opção</strong> para
                                    confirmar que você concorre às vagas PcD e
                                    liberar o envio dos documentos abaixo.
                                </Message>

                                <div
                                    v-if="concorrePcdAtivo"
                                    class="mt-6 flex flex-col gap-4"
                                >
                                    <p
                                        class="text-sm font-medium text-foreground"
                                    >
                                        Documentos para concorrência PcD
                                    </p>
                                    <CandidaturaSpecialDocumentUpload
                                        :application-id="application.id"
                                        document-kind="pcd_declaracao"
                                        title="Declaração de Pessoa com Deficiência"
                                        description="Documento conforme modelo constante no edital do processo."
                                        accepted-hint="Formatos aceitos: PDF, JPG ou PNG · até 10 MB."
                                        :uploaded-doc="pcdDeclaracaoDoc"
                                        :is-finalized="isFinalized"
                                    />
                                    <CandidaturaSpecialDocumentUpload
                                        :application-id="application.id"
                                        document-kind="pcd_laudo"
                                        title="Laudo médico ou parecer multiprofissional / Carteira PcD"
                                        description="Laudo médico ou parecer de equipe multiprofissional, emitido nos últimos três meses, ou laudo com validade indeterminada; ou Carteira de Pessoa com Deficiência (PcD)."
                                        accepted-hint="Formatos aceitos: PDF, JPG ou PNG · até 10 MB."
                                        :uploaded-doc="pcdLaudoDoc"
                                        :is-finalized="isFinalized"
                                    />
                                </div>

                                <div
                                    class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div
                                        v-if="!step1Committed && !isFinalized"
                                        class="min-w-0 flex-1"
                                    >
                                        <Message
                                            severity="info"
                                            :closable="false"
                                        >
                                            Salve <strong>Sim</strong> ou
                                            <strong>Não</strong> antes de
                                            continuar.
                                        </Message>
                                    </div>
                                    <div class="flex shrink-0 justify-end">
                                        <Button
                                            label="Continuar"
                                            icon="pi pi-arrow-right"
                                            icon-pos="right"
                                            size="small"
                                            :disabled="
                                                isFinalized || !canLeavePcdStep
                                            "
                                            @click="activeStep = '2'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </StepPanel>

                        <!-- Etapa 2: Dados pessoais -->
                        <StepPanel value="2">
                            <div class="py-2">
                                <CandidatePersonalDataPanel
                                    ref="personalDataPanelRef"
                                    :user="profileUser"
                                    :ufs="props.ufs"
                                    :must-verify-email="props.mustVerifyEmail"
                                    :is-finalized="isFinalized"
                                    :enrollment-step="2"
                                />

                                <div
                                    class="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-border/60 pt-5"
                                >
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
                                        :disabled="isFinalized"
                                        @click="activeStep = '3'"
                                    />
                                </div>
                            </div>
                        </StepPanel>

                        <!-- Etapa 3: Títulos para pontuação -->
                        <StepPanel value="3">
                            <div class="py-2">
                                <div class="mb-5 flex items-start gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                    >
                                        <Award :size="20" stroke-width="2" />
                                    </div>
                                    <div class="min-w-0">
                                        <h3
                                            class="text-base font-semibold tracking-tight text-foreground"
                                        >
                                            Títulos para pontuação
                                        </h3>
                                        <p
                                            class="mt-0.5 max-w-2xl text-sm text-muted-foreground"
                                        >
                                            Envie os comprovantes dos títulos
                                            previstos no edital deste processo.
                                            Cada item lista a pontuação,
                                            formatos aceitos e instruções.
                                        </p>
                                    </div>
                                </div>

                                <CandidateTitleGroupsUpload
                                    :title-groups="
                                        application.selection_process
                                            ?.title_groups ?? []
                                    "
                                    :documents="application.documents ?? []"
                                    :application-id="application.id"
                                    :is-finalized="isFinalized"
                                />

                                <div
                                    class="mt-6 flex justify-between gap-2 border-t border-border/60 pt-5"
                                >
                                    <Button
                                        label="Anterior"
                                        icon="pi pi-arrow-left"
                                        severity="secondary"
                                        outlined
                                        size="small"
                                        @click="activeStep = '2'"
                                    />
                                    <Button
                                        label="Próximo"
                                        icon="pi pi-arrow-right"
                                        icon-pos="right"
                                        size="small"
                                        :disabled="isFinalized"
                                        @click="activeStep = '4'"
                                    />
                                </div>
                            </div>
                        </StepPanel>

                        <!-- Etapa 4: Documentos obrigatórios -->
                        <StepPanel value="4">
                            <div class="py-4">
                                <div class="mb-2 flex items-center gap-2">
                                    <FileText :size="18" class="text-primary" />
                                    <h3 class="text-base font-semibold">
                                        Documentos obrigatórios
                                    </h3>
                                </div>
                                <p class="mb-5 text-sm text-muted-foreground">
                                    Envie os documentos exigidos pelo processo.
                                    Documentos recusados precisam ser reenviados
                                    antes de finalizar a inscrição.
                                </p>

                                <RequiredDocumentsStatusList
                                    :documents="
                                        application.selection_process
                                            ?.required_documents ?? []
                                    "
                                    :uploaded-docs="application.documents ?? []"
                                    :application-id="application.id"
                                    :is-finalized="isFinalized"
                                />

                                <div class="mt-6 flex justify-between gap-2">
                                    <Button
                                        label="Anterior"
                                        icon="pi pi-arrow-left"
                                        severity="secondary"
                                        outlined
                                        size="small"
                                        @click="activeStep = '3'"
                                    />
                                    <Button
                                        label="Revisar inscrição"
                                        icon="pi pi-arrow-right"
                                        icon-pos="right"
                                        size="small"
                                        @click="activeStep = '5'"
                                    />
                                </div>
                            </div>
                        </StepPanel>

                        <!-- Etapa 5: Revisão e envio -->
                        <StepPanel value="5">
                            <div class="py-4">
                                <div class="mb-5 flex items-center gap-2">
                                    <CheckCircle2
                                        :size="18"
                                        class="text-primary"
                                    />
                                    <h3 class="text-base font-semibold">
                                        Revisão e envio
                                    </h3>
                                </div>

                                <div class="flex flex-col gap-4">
                                    <div
                                        v-if="
                                            !isFinalized &&
                                            pendingRequiredDocs.length > 0
                                        "
                                        class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20"
                                    >
                                        <div class="flex items-start gap-3">
                                            <AlertTriangle
                                                :size="18"
                                                class="mt-0.5 shrink-0 text-amber-600 dark:text-amber-400"
                                            />
                                            <div>
                                                <p
                                                    class="font-semibold text-amber-800 dark:text-amber-300"
                                                >
                                                    Pendências encontradas
                                                </p>
                                                <p
                                                    class="mt-0.5 text-sm text-amber-700 dark:text-amber-400"
                                                >
                                                    Os documentos abaixo são
                                                    obrigatórios e ainda não
                                                    foram enviados:
                                                </p>
                                                <ul
                                                    class="mt-2 flex flex-col gap-1"
                                                >
                                                    <li
                                                        v-for="doc in pendingRequiredDocs"
                                                        :key="doc.id"
                                                        class="flex items-center gap-1.5 text-sm text-amber-800 dark:text-amber-300"
                                                    >
                                                        <i
                                                            class="pi pi-times-circle text-amber-500"
                                                        />
                                                        {{ doc.nome }}
                                                    </li>
                                                </ul>
                                                <Button
                                                    label="Ir para documentos"
                                                    icon="pi pi-arrow-right"
                                                    icon-pos="right"
                                                    size="small"
                                                    severity="warn"
                                                    class="mt-3"
                                                    @click="activeStep = '4'"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="
                                            !isFinalized &&
                                            pendingPcdDocLabels.length > 0
                                        "
                                        class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20"
                                    >
                                        <div class="flex items-start gap-3">
                                            <AlertTriangle
                                                :size="18"
                                                class="mt-0.5 shrink-0 text-amber-600 dark:text-amber-400"
                                            />
                                            <div>
                                                <p
                                                    class="font-semibold text-amber-800 dark:text-amber-300"
                                                >
                                                    Documentos PcD pendentes
                                                </p>
                                                <ul
                                                    class="mt-2 flex flex-col gap-1"
                                                >
                                                    <li
                                                        v-for="(
                                                            label, idx
                                                        ) in pendingPcdDocLabels"
                                                        :key="idx"
                                                        class="flex items-center gap-1.5 text-sm text-amber-800 dark:text-amber-300"
                                                    >
                                                        <i
                                                            class="pi pi-times-circle text-amber-500"
                                                        />
                                                        {{ label }}
                                                    </li>
                                                </ul>
                                                <Button
                                                    label="Ir para etapa PcD"
                                                    icon="pi pi-arrow-right"
                                                    icon-pos="right"
                                                    size="small"
                                                    severity="warn"
                                                    class="mt-3"
                                                    @click="activeStep = '1'"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-xl border border-border p-4"
                                    >
                                        <p class="text-sm font-semibold">
                                            Ações afirmativas PcD
                                        </p>
                                        <p
                                            class="mt-1 text-sm text-muted-foreground"
                                        >
                                            <span v-if="concorrePcdAtivo">
                                                Você optou por
                                                <strong>concorrer</strong> às
                                                vagas PcD.
                                            </span>
                                            <span v-else>
                                                Você optou por
                                                <strong>não concorrer</strong>
                                                às vagas PcD.
                                            </span>
                                        </p>
                                        <ul
                                            v-if="
                                                concorrePcdAtivo &&
                                                (pcdDeclaracaoDoc ||
                                                    pcdLaudoDoc)
                                            "
                                            class="mt-2 flex flex-col gap-1 text-sm"
                                        >
                                            <li
                                                v-if="pcdDeclaracaoDoc"
                                                class="text-muted-foreground"
                                            >
                                                Declaração:
                                                {{
                                                    pcdDeclaracaoDoc.nome_arquivo
                                                }}
                                            </li>
                                            <li
                                                v-if="pcdLaudoDoc"
                                                class="text-muted-foreground"
                                            >
                                                Laudo / carteira:
                                                {{ pcdLaudoDoc.nome_arquivo }}
                                            </li>
                                        </ul>
                                    </div>

                                    <div
                                        class="rounded-xl border border-border p-4"
                                    >
                                        <div
                                            class="mb-3 flex items-center justify-between"
                                        >
                                            <p class="text-sm font-semibold">
                                                Perfil cadastral
                                            </p>
                                            <div class="flex gap-1">
                                                <Button
                                                    v-if="!isFinalized"
                                                    label="Editar perfil"
                                                    icon="pi pi-user"
                                                    text
                                                    size="small"
                                                    @click="
                                                        openProfileEditOnStep2
                                                    "
                                                />
                                                <Button
                                                    v-if="!isFinalized"
                                                    label="Ver etapa"
                                                    icon="pi pi-arrow-left"
                                                    text
                                                    size="small"
                                                    @click="activeStep = '2'"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            class="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-3"
                                        >
                                            <div
                                                v-for="row in profileReviewSummary"
                                                :key="row.label"
                                            >
                                                <span
                                                    class="text-muted-foreground"
                                                    >{{ row.label }}:
                                                </span>
                                                <span class="font-medium">{{
                                                    row.value ?? '—'
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-xl border border-border p-4"
                                    >
                                        <div
                                            class="mb-3 flex items-center justify-between"
                                        >
                                            <p class="text-sm font-semibold">
                                                Comprovantes de títulos
                                            </p>
                                            <Button
                                                v-if="!isFinalized"
                                                label="Gerenciar"
                                                icon="pi pi-pencil"
                                                text
                                                size="small"
                                                @click="activeStep = '3'"
                                            />
                                        </div>
                                        <div
                                            class="flex flex-col gap-2 text-sm"
                                        >
                                            <div>
                                                <span
                                                    class="text-muted-foreground"
                                                    >Itens com comprovante:
                                                </span>
                                                <span
                                                    class="font-medium tabular-nums"
                                                >
                                                    {{
                                                        itemsWithUploadedTitleCount
                                                    }}
                                                    de {{ totalTitleItems }}
                                                </span>
                                            </div>
                                            <div>
                                                <span
                                                    class="text-muted-foreground"
                                                    >Arquivos enviados:
                                                </span>
                                                <span
                                                    class="font-medium tabular-nums"
                                                    >{{
                                                        uploadedTitleFilesCount
                                                    }}</span
                                                >
                                            </div>
                                            <p
                                                v-if="totalTitleItems === 0"
                                                class="text-xs text-muted-foreground"
                                            >
                                                Este processo não exige tabela
                                                de títulos.
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-xl border border-border p-4"
                                    >
                                        <div
                                            class="mb-3 flex items-center justify-between"
                                        >
                                            <p class="text-sm font-semibold">
                                                Documentos enviados
                                            </p>
                                            <Button
                                                v-if="!isFinalized"
                                                label="Gerenciar"
                                                icon="pi pi-upload"
                                                text
                                                size="small"
                                                @click="activeStep = '4'"
                                            />
                                        </div>
                                        <div
                                            v-if="
                                                (application.documents ?? [])
                                                    .length === 0
                                            "
                                            class="text-sm text-muted-foreground"
                                        >
                                            Nenhum documento enviado.
                                        </div>
                                        <ul
                                            v-else
                                            class="flex flex-col gap-1.5"
                                        >
                                            <li
                                                v-for="doc in application.documents"
                                                :key="doc.id"
                                                class="flex items-center gap-2 text-sm"
                                            >
                                                <i
                                                    class="pi pi-file-o shrink-0 text-muted-foreground"
                                                />
                                                <div class="min-w-0 flex-1">
                                                    <p
                                                        class="truncate font-medium"
                                                    >
                                                        {{
                                                            documentRowLabel(
                                                                doc,
                                                            )
                                                        }}
                                                    </p>
                                                    <p
                                                        v-if="
                                                            documentLinkedTitle(
                                                                doc,
                                                            )
                                                        "
                                                        class="truncate text-xs text-muted-foreground"
                                                    >
                                                        {{ doc.nome_arquivo }}
                                                    </p>
                                                </div>
                                                <Tag
                                                    :value="
                                                        {
                                                            enviado: 'Enviado',
                                                            em_analise:
                                                                'Em análise',
                                                            aprovado:
                                                                'Aprovado',
                                                            recusado:
                                                                'Recusado',
                                                        }[doc.status] ??
                                                        doc.status
                                                    "
                                                    :severity="
                                                        (
                                                            {
                                                                enviado:
                                                                    'secondary',
                                                                em_analise:
                                                                    'warn',
                                                                aprovado:
                                                                    'success',
                                                                recusado:
                                                                    'danger',
                                                            } as Record<
                                                                string,
                                                                | 'secondary'
                                                                | 'success'
                                                                | 'warn'
                                                                | 'danger'
                                                            >
                                                        )[doc.status] ??
                                                        'secondary'
                                                    "
                                                    class="text-xs"
                                                />
                                            </li>
                                        </ul>
                                    </div>

                                    <div
                                        v-if="
                                            (
                                                application.selection_process
                                                    ?.title_groups ?? []
                                            ).length
                                        "
                                        class="rounded-xl border border-border p-4"
                                    >
                                        <p class="text-sm font-semibold">
                                            Títulos para pontuação
                                        </p>
                                        <ul
                                            class="mt-3 flex flex-col gap-2 text-sm"
                                        >
                                            <li
                                                v-for="g in application
                                                    .selection_process
                                                    ?.title_groups ?? []"
                                                :key="g.id"
                                                class="flex items-center justify-between gap-2 rounded-lg bg-muted/30 px-3 py-2"
                                            >
                                                <span
                                                    class="font-medium text-foreground"
                                                    >{{ g.name }}</span
                                                >
                                                <span
                                                    class="shrink-0 text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        g.items.length
                                                    }}
                                                    item(ns)
                                                </span>
                                            </li>
                                        </ul>
                                    </div>

                                    <div v-if="!isFinalized">
                                        <Message
                                            severity="info"
                                            :closable="false"
                                            class="mb-4"
                                        >
                                            Ao confirmar, sua inscrição será
                                            enviada para análise e você receberá
                                            um e-mail de confirmação com o
                                            comprovante.
                                        </Message>

                                        <div
                                            class="mb-4 flex items-start gap-3 rounded-xl border border-border bg-muted/20 px-4 py-3"
                                        >
                                            <Checkbox
                                                v-model="confirmDeclaration"
                                                :binary="true"
                                                input-id="confirm-declaration"
                                                class="mt-0.5 shrink-0"
                                            />
                                            <label
                                                for="confirm-declaration"
                                                class="cursor-pointer text-sm leading-relaxed text-foreground"
                                            >
                                                Declaro que as informações e
                                                documentos enviados são
                                                verdadeiros e assumo total
                                                responsabilidade pelas
                                                informações prestadas.
                                            </label>
                                        </div>

                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <Button
                                                label="Anterior"
                                                icon="pi pi-arrow-left"
                                                severity="secondary"
                                                outlined
                                                size="small"
                                                @click="activeStep = '4'"
                                            />
                                            <Button
                                                label="Finalizar inscrição"
                                                icon="pi pi-check"
                                                size="small"
                                                :disabled="!canSubmit"
                                                @click="submitApplication"
                                            />
                                        </div>

                                        <p
                                            v-if="!canSubmit"
                                            class="mt-2 text-right text-xs text-muted-foreground"
                                        >
                                            <span
                                                v-if="
                                                    pendingRequiredDocs.length >
                                                    0
                                                "
                                            >
                                                Envie todos os documentos
                                                obrigatórios para continuar.
                                            </span>
                                            <span v-else-if="!pcdDocsComplete">
                                                Envie os documentos exigidos
                                                para concorrência PcD ou altere
                                                sua opção na primeira etapa.
                                            </span>
                                            <span v-else>
                                                Confirme a declaração para
                                                finalizar.
                                            </span>
                                        </p>
                                    </div>

                                    <div v-else class="space-y-4">
                                        <div
                                            class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30"
                                        >
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <CheckCircle2
                                                    :size="20"
                                                    class="text-green-600 dark:text-green-400"
                                                />
                                                <div>
                                                    <p
                                                        class="font-semibold text-green-800 dark:text-green-300"
                                                    >
                                                        Inscrição finalizada
                                                    </p>
                                                    <p
                                                        class="text-sm text-green-700 dark:text-green-400"
                                                    >
                                                        Protocolo:
                                                        <strong>{{
                                                            application.numero_protocolo
                                                        }}</strong>
                                                    </p>
                                                    <p
                                                        class="mt-1 text-xs text-green-700/90 dark:text-green-400/90"
                                                    >
                                                        Emita comprovantes e
                                                        declarações na seção
                                                        abaixo, para fins
                                                        profissionais.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </StepPanel>
                    </ApplicationModernStepper>
                </template>
            </Card>

            <ApplicationProfessionalDocuments
                :application-id="application.id"
                :is-finalized="isFinalized"
                :professional-documents="professionalDocuments"
                :appeal-stages="appealStages"
                :appeals="appeals"
                :has-open-recurso-window="hasOpenRecursoWindow"
            />
        </div>
    </div>
</template>
