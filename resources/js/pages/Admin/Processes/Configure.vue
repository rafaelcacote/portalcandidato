<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    Award,
    CalendarRange,
    FileBadge,
    FileText,
    GraduationCap,
    Layers,
    Settings2,
    Users,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import Fluid from 'primevue/fluid';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';
import { edit } from '@/routes/admin/processes';
import {
    destroy as destroyCriteria,
    store as storeCriteria,
} from '@/routes/admin/processes/criteria';
import {
    destroy as destroyEdital,
    store as storeEdital,
} from '@/routes/admin/processes/edital';
import {
    destroy as destroyRequiredDocument,
    store as storeRequiredDocument,
    update as updateRequiredDocument,
} from '@/routes/admin/processes/required-documents';
import {
    destroy as destroyTitleGroup,
    store as storeTitleGroup,
    update as updateTitleGroup,
} from '@/routes/admin/processes/title-groups';
import {
    destroy as destroyTitleItem,
    store as storeTitleItem,
    update as updateTitleItem,
} from '@/routes/admin/processes/title-groups/items';

type ProcessTitleItem = {
    id: number;
    process_title_group_id: number;
    code: string;
    title: string;
    score_per_unit: string | number;
    score_unit: string;
    max_quantity?: number | null;
    period_rule?: string | null;
    requires_attachment: boolean;
    accepted_formats?: string[] | null;
    max_file_size_mb: number;
    candidate_instructions?: string | null;
    is_active: boolean;
    order: number;
};

type ProcessTitleGroup = {
    id: number;
    selection_process_id: number;
    code: string;
    name: string;
    description?: string | null;
    max_score: string | number;
    order: number;
    is_active: boolean;
    items?: ProcessTitleItem[];
};

type SelectionProcess = {
    id: number;
    titulo: string;
    descricao?: string | null;
    regras?: string | null;
    status: string;
    tipo_programa?: string | null;
    inscricao_inicio_em?: string | null;
    inscricao_fim_em?: string | null;
    stages?: Array<{ id: number; nome: string; ordem: number }>;
    title_groups?: ProcessTitleGroup[];
    required_documents?: Array<{
        id: number;
        tipo_documento_id?: number | null;
        nome: string;
        descricao?: string | null;
        formatos_aceitos?: string[] | null;
        tamanho_max_mb: number;
        obrigatorio: boolean;
        gerado_por_template?: boolean;
        tipo_documento?: { id: number; descricao: string } | null;
    }>;
    criteria?: Array<{
        id: number;
        nome: string;
        peso: number;
        ordem: number;
        pontuacao_max: number;
    }>;
    evaluator_assignments?: Array<{ id: number }>;
    edital_download_url?: string | null;
};

const props = defineProps<{
    selectionProcess: SelectionProcess;
    tiposDocumento: Array<{ id: number; descricao: string }>;
}>();

const vTooltip = Tooltip;
const confirm = useConfirm();

/* ─── Edital ─────────────────────────────────────────────── */

const editalForm = useForm({
    edital: null as File | null,
});
const editalInputKey = ref(0);

const hasEditalPdf = computed(
    () => Boolean(props.selectionProcess.edital_download_url),
);

const onEditalFileChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    editalForm.edital = input.files?.[0] ?? null;
};

const submitEditalPdf = (): void => {
    editalForm.post(storeEdital(props.selectionProcess.id).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            editalForm.reset();
            editalInputKey.value += 1;
        },
    });
};

const confirmRemoveEditalPdf = (): void => {
    confirm.require({
        header: 'Excluir edital',
        message:
            'Deseja excluir o PDF do edital deste processo? Os candidatos deixarão de ver o edital até que você envie um novo arquivo.',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Remover',
        rejectProps: { outlined: true, icon: 'pi pi-times' },
        acceptProps: { severity: 'danger', icon: 'pi pi-trash' },
        accept: () => {
            router.delete(destroyEdital(props.selectionProcess.id).url, {
                preserveScroll: true,
            });
        },
    });
};

/* ─── Documentos ──────────────────────────────────────────── */

const requiredDocumentForm = useForm({
    tipo_documento_id: null as number | null,
    descricao: '',
    formatos_aceitos: 'pdf,jpg,png',
    tamanho_max_mb: 10,
    obrigatorio: true,
});
const showAddDocumentForm = ref(false);

const storeRequiredDocumentAction = (): void => {
    requiredDocumentForm.post(
        storeRequiredDocument(props.selectionProcess.id).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                requiredDocumentForm.reset();
                requiredDocumentForm.formatos_aceitos = 'pdf,jpg,png';
                requiredDocumentForm.tamanho_max_mb = 10;
                requiredDocumentForm.obrigatorio = true;
                showAddDocumentForm.value = false;
            },
        },
    );
};

const confirmRemoveRequiredDocument = (doc: {
    id: number;
    tipo_documento?: { descricao: string } | null;
}): void => {
    const docName = doc.tipo_documento?.descricao ?? 'documento';
    confirm.require({
        header: 'Remover documento exigido',
        message: `Deseja remover o documento "${docName}" deste processo?`,
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Remover',
        rejectProps: { outlined: true, icon: 'pi pi-times' },
        acceptProps: { severity: 'danger', icon: 'pi pi-trash' },
        accept: () => {
            router.delete(
                destroyRequiredDocument({
                    selectionProcess: props.selectionProcess.id,
                    processRequiredDocument: doc.id,
                }).url,
                { preserveScroll: true },
            );
        },
    });
};

const editingDocumentId = ref<number | null>(null);
const editDocumentForm = useForm({
    descricao: '' as string,
    formatos_aceitos: 'pdf,jpg,png',
    tamanho_max_mb: 10,
    obrigatorio: true,
});

const openEditDocument = (doc: {
    id: number;
    descricao?: string | null;
    formatos_aceitos?: string[] | null;
    tamanho_max_mb: number;
    obrigatorio: boolean;
}): void => {
    editingDocumentId.value = doc.id;
    editDocumentForm.descricao = doc.descricao ?? '';
    editDocumentForm.formatos_aceitos =
        doc.formatos_aceitos?.join(',') ?? 'pdf,jpg,png';
    editDocumentForm.tamanho_max_mb = doc.tamanho_max_mb;
    editDocumentForm.obrigatorio = doc.obrigatorio;
};

const cancelEditDocument = (): void => {
    editingDocumentId.value = null;
    editDocumentForm.reset();
};

const updateDocumentAction = (docId: number): void => {
    editDocumentForm.put(
        updateRequiredDocument({
            selectionProcess: props.selectionProcess.id,
            processRequiredDocument: docId,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                editingDocumentId.value = null;
            },
        },
    );
};

/* ─── Títulos (grupos + itens) ────────────────────────────── */

const titleGroupForm = useForm({
    code: '',
    name: '',
    description: '',
    max_score: 0 as number,
    order: 0,
});
const showAddGroupForm = ref(false);

const titleItemForm = useForm({
    code: '',
    title: '',
    score_per_unit: 0 as number,
    score_unit: '',
    max_quantity: null as number | null,
    period_rule: '',
    requires_attachment: true,
    accepted_formats: 'pdf,jpg,png',
    max_file_size_mb: 10,
    candidate_instructions: '',
    order: 0,
});
const showAddItemFormForGroup = ref<number | null>(null);
const expandedGroups = ref<Set<number>>(new Set());

const toggleGroup = (id: number): void => {
    if (expandedGroups.value.has(id)) {
        expandedGroups.value.delete(id);
    } else {
        expandedGroups.value.add(id);
    }
};

const isGroupExpanded = (id: number): boolean =>
    expandedGroups.value.has(id);

const openAddItemForm = (groupId: number): void => {
    showAddItemFormForGroup.value = groupId;
    expandedGroups.value.add(groupId);
};

const closeAddItemForm = (): void => {
    showAddItemFormForGroup.value = null;
    titleItemForm.reset();
    titleItemForm.requires_attachment = true;
    titleItemForm.accepted_formats = 'pdf,jpg,png';
    titleItemForm.max_file_size_mb = 10;
};

const storeTitleGroupAction = (): void => {
    titleGroupForm.post(storeTitleGroup(props.selectionProcess.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            titleGroupForm.reset();
            showAddGroupForm.value = false;
        },
    });
};

const storeTitleItemAction = (): void => {
    const groupId = showAddItemFormForGroup.value;

    if (!groupId) {
        return;
    }

    titleItemForm.post(
        storeTitleItem({
            selectionProcess: props.selectionProcess.id,
            titleGroup: groupId,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => closeAddItemForm(),
        },
    );
};

const confirmRemoveTitleGroup = (group: {
    id: number;
    name: string;
}): void => {
    confirm.require({
        header: 'Remover grupo de títulos',
        message: `Deseja remover o grupo "${group.name}" e todos os seus itens? Esta ação não pode ser desfeita.`,
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Remover',
        rejectProps: { outlined: true, icon: 'pi pi-times' },
        acceptProps: { severity: 'danger', icon: 'pi pi-trash' },
        accept: () => {
            router.delete(
                destroyTitleGroup({
                    selectionProcess: props.selectionProcess.id,
                    titleGroup: group.id,
                }).url,
                { preserveScroll: true },
            );
        },
    });
};

const confirmRemoveTitleItem = (
    group: { id: number },
    item: { id: number; title: string },
): void => {
    confirm.require({
        header: 'Remover item',
        message: `Deseja remover o item "${item.title}"?`,
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Remover',
        rejectProps: { outlined: true, icon: 'pi pi-times' },
        acceptProps: { severity: 'danger', icon: 'pi pi-trash' },
        accept: () => {
            router.delete(
                destroyTitleItem({
                    selectionProcess: props.selectionProcess.id,
                    titleGroup: group.id,
                    item: item.id,
                }).url,
                { preserveScroll: true },
            );
        },
    });
};

const editingGroupId = ref<number | null>(null);
const editGroupForm = useForm({
    code: '',
    name: '',
    description: '',
    max_score: 0 as number,
    order: 0,
});

const openEditGroup = (group: ProcessTitleGroup): void => {
    editingGroupId.value = group.id;
    editGroupForm.code = group.code;
    editGroupForm.name = group.name;
    editGroupForm.description = group.description ?? '';
    editGroupForm.max_score = Number(group.max_score);
    editGroupForm.order = group.order;
};

const cancelEditGroup = (): void => {
    editingGroupId.value = null;
    editGroupForm.reset();
};

const updateGroupAction = (groupId: number): void => {
    editGroupForm.put(
        updateTitleGroup({
            selectionProcess: props.selectionProcess.id,
            titleGroup: groupId,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                editingGroupId.value = null;
            },
        },
    );
};

const editingItemId = ref<number | null>(null);
const editItemForm = useForm({
    code: '',
    title: '',
    score_per_unit: 0 as number,
    score_unit: '',
    max_quantity: null as number | null,
    period_rule: '',
    requires_attachment: true,
    accepted_formats: 'pdf,jpg,png',
    max_file_size_mb: 10,
    candidate_instructions: '',
    order: 0,
});

const openEditItem = (item: ProcessTitleItem): void => {
    editingItemId.value = item.id;
    editItemForm.code = item.code;
    editItemForm.title = item.title;
    editItemForm.score_per_unit = Number(item.score_per_unit);
    editItemForm.score_unit = item.score_unit;
    editItemForm.max_quantity = item.max_quantity ?? null;
    editItemForm.period_rule = item.period_rule ?? '';
    editItemForm.requires_attachment = item.requires_attachment;
    editItemForm.accepted_formats =
        item.accepted_formats?.join(',') ?? 'pdf,jpg,png';
    editItemForm.max_file_size_mb = item.max_file_size_mb;
    editItemForm.candidate_instructions = item.candidate_instructions ?? '';
    editItemForm.order = item.order;
};

const cancelEditItem = (): void => {
    editingItemId.value = null;
    editItemForm.reset();
};

const updateItemAction = (
    groupId: number,
    itemId: number,
): void => {
    editItemForm.put(
        updateTitleItem({
            selectionProcess: props.selectionProcess.id,
            titleGroup: groupId,
            item: itemId,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                editingItemId.value = null;
            },
        },
    );
};

/* ─── Critérios ───────────────────────────────────────────── */

const criteriaForm = useForm({
    nome: '',
    peso: 1,
    pontuacao_max: 10,
    ordem: 1,
});
const showAddCriteriaForm = ref(false);

const storeCriteriaAction = (): void => {
    criteriaForm.post(storeCriteria(props.selectionProcess.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            criteriaForm.reset();
            criteriaForm.peso = 1;
            criteriaForm.pontuacao_max = 10;
            criteriaForm.ordem = 1;
            showAddCriteriaForm.value = false;
        },
    });
};

const deleteCriteriaAction = (criteriaId: number): void => {
    router.delete(
        destroyCriteria({
            selectionProcess: props.selectionProcess.id,
            processCriteria: criteriaId,
        }).url,
        { preserveScroll: true },
    );
};

/* ─── Helpers ─────────────────────────────────────────────── */

const formatosToList = (formatos?: string[] | null): string[] => {
    if (!formatos) {
        return [];
    }

    return formatos.filter((value): value is string => Boolean(value));
};

const formatPontuacao = (value: string | number): string => {
    const parsed = typeof value === 'number' ? value : Number(value);

    if (Number.isNaN(parsed)) {
        return String(value);
    }

    return parsed.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatScore = (value: string | number): string => {
    const parsed = typeof value === 'number' ? value : Number(value);

    if (Number.isNaN(parsed)) {
        return String(value);
    }

    return parsed.toLocaleString('pt-BR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
};

const formatPeriod = (value?: string | null): string => {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(parsed);
};

const periodLabel = computed(() => {
    const start = formatPeriod(props.selectionProcess.inscricao_inicio_em);
    const end = formatPeriod(props.selectionProcess.inscricao_fim_em);

    if (start === '-' && end === '-') {
        return 'Não definido';
    }

    return `${start} → ${end}`;
});

const programTypeLabel = computed((): string => {
    const t = props.selectionProcess.tipo_programa;

    if (t === 'mestrado') {
        return 'Mestrado';
    }

    if (t === 'doutorado') {
        return 'Doutorado';
    }

    return 'Não definido';
});

const statusSeverity: Record<
    string,
    'secondary' | 'success' | 'warn' | 'danger'
> = {
    rascunho: 'secondary',
    ativo: 'success',
    encerrado: 'warn',
};

/* ─── Contagens ───────────────────────────────────────────── */

const requiredDocumentsCount = computed(
    () => props.selectionProcess.required_documents?.length ?? 0,
);
const titleGroupsCount = computed(
    () => props.selectionProcess.title_groups?.length ?? 0,
);
const titleGroupsTotalMaxScore = computed(() =>
    (props.selectionProcess.title_groups ?? []).reduce(
        (acc, g) => acc + Number(g.max_score),
        0,
    ),
);
const criteriaCount = computed(
    () => props.selectionProcess.criteria?.length ?? 0,
);
const stagesCount = computed(
    () => props.selectionProcess.stages?.length ?? 0,
);
const evaluatorsCount = computed(
    () => props.selectionProcess.evaluator_assignments?.length ?? 0,
);

/* ─── Navegação lateral ───────────────────────────────────── */

type SectionKey = 'edital' | 'documentos' | 'titulos' | 'criterios' | 'etapas';

const activeSection = ref<SectionKey>('documentos');

const navSections = computed<
    Array<{
        key: SectionKey;
        label: string;
        icon: typeof FileBadge;
        count: number | null;
        done: boolean;
    }>
>(() => [
    {
        key: 'edital',
        label: 'Edital PDF',
        icon: FileBadge,
        count: null,
        done: hasEditalPdf.value,
    },
    {
        key: 'documentos',
        label: 'Documentos',
        icon: FileText,
        count: requiredDocumentsCount.value,
        done: requiredDocumentsCount.value > 0,
    },
    {
        key: 'titulos',
        label: 'Títulos',
        icon: GraduationCap,
        count: titleGroupsCount.value,
        done: titleGroupsCount.value > 0,
    },
    {
        key: 'criterios',
        label: 'Critérios',
        icon: Award,
        count: criteriaCount.value,
        done: criteriaCount.value > 0,
    },
    {
        key: 'etapas',
        label: 'Etapas',
        icon: Layers,
        count: stagesCount.value,
        done: stagesCount.value > 0,
    },
]);

const completionChecks = computed(() => [
    { label: 'Edital PDF enviado', done: hasEditalPdf.value },
    { label: 'Documentos configurados', done: requiredDocumentsCount.value > 0 },
    { label: 'Títulos configurados', done: titleGroupsCount.value > 0 },
    { label: 'Critérios definidos', done: criteriaCount.value > 0 },
    { label: 'Etapas criadas', done: stagesCount.value > 0 },
]);

const completionDoneCount = computed(
    () => completionChecks.value.filter((c) => c.done).length,
);
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 lg:px-10 md:py-4">
        <ConfirmDialog />

        <div class="mx-auto flex w-full max-w-[1400px] flex-col gap-6">

            <!-- ─── Cabeçalho da página ────────────────────────────── -->
            <div class="flex items-start justify-between gap-4 py-2">
                <div class="flex flex-col gap-1">
                    <div
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <Settings2 :size="12" />
                        <span class="uppercase tracking-wide"
                            >Processo Seletivo</span
                        >
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{ props.selectionProcess.titulo }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Gerencie documentos, títulos, critérios e etapas do
                        processo.
                    </p>
                </div>
                <Link :href="edit(props.selectionProcess.id).url">
                    <Button
                        label="Editar processo"
                        icon="pi pi-pencil"
                        outlined
                        size="small"
                    />
                </Link>
            </div>

            <!-- ─── Tabs mobile (visível apenas abaixo de lg) ─────── -->
            <div
                class="flex overflow-x-auto gap-1 rounded-xl border bg-muted/30 p-1 lg:hidden"
            >
                <button
                    v-for="section in navSections"
                    :key="section.key"
                    type="button"
                    class="flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                    :class="
                        activeSection === section.key
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeSection = section.key"
                >
                    <component :is="section.icon" :size="13" />
                    {{ section.label }}
                    <span
                        v-if="section.count !== null"
                        class="rounded-full bg-muted px-1.5 py-0.5 text-xs font-medium"
                    >
                        {{ section.count }}
                    </span>
                </button>
            </div>

            <!-- ─── Layout principal: sidebar + conteúdo ──────────── -->
            <div class="flex items-start gap-6">

                <!-- ─── Sidebar (desktop) ─────────────────────────── -->
                <aside
                    class="hidden w-60 shrink-0 flex-col gap-3 self-start lg:sticky lg:top-6 lg:flex xl:w-64"
                >
                    <!-- Resumo do processo -->
                    <div class="rounded-xl border bg-card p-4 flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                                >Status</span
                            >
                            <Tag
                                :value="props.selectionProcess.status"
                                :severity="
                                    statusSeverity[
                                        props.selectionProcess.status
                                    ] ?? 'secondary'
                                "
                            />
                        </div>
                        <div class="flex flex-col gap-2.5">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-6 w-6 items-center justify-center rounded-md bg-violet-500/10 text-violet-600"
                                >
                                    <GraduationCap :size="13" />
                                </div>
                                <span class="text-sm text-muted-foreground">{{
                                    programTypeLabel
                                }}</span>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div
                                    class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-500/10 text-blue-600"
                                >
                                    <CalendarRange :size="13" />
                                </div>
                                <span
                                    class="text-xs leading-relaxed text-muted-foreground"
                                    >{{ periodLabel }}</span
                                >
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-600"
                                >
                                    <Users :size="13" />
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    {{ evaluatorsCount }}
                                    {{
                                        evaluatorsCount === 1
                                            ? 'avaliador'
                                            : 'avaliadores'
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Navegação de seções -->
                    <div class="overflow-hidden rounded-xl border bg-card">
                        <div class="border-b px-3 py-2.5">
                            <span
                                class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                                >Configuração</span
                            >
                        </div>
                        <div class="flex flex-col gap-0.5 p-1.5">
                            <button
                                v-for="section in navSections"
                                :key="section.key"
                                type="button"
                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left transition-colors"
                                :class="
                                    activeSection === section.key
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-foreground hover:bg-muted/60'
                                "
                                @click="activeSection = section.key"
                            >
                                <component
                                    :is="section.icon"
                                    :size="15"
                                    class="shrink-0"
                                />
                                <span class="flex-1 text-sm font-medium">{{
                                    section.label
                                }}</span>
                                <span
                                    v-if="section.count !== null"
                                    class="min-w-5 rounded-full px-1.5 py-0.5 text-center text-xs font-semibold"
                                    :class="
                                        activeSection === section.key
                                            ? 'bg-white/20 text-primary-foreground'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ section.count }}
                                </span>
                                <i
                                    v-else
                                    :class="
                                        section.done
                                            ? 'pi pi-check-circle text-emerald-500'
                                            : 'pi pi-circle text-muted-foreground/40'
                                    "
                                    class="text-xs"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Barra de progresso -->
                    <div class="rounded-xl border bg-card p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <span
                                class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                                >Progresso</span
                            >
                            <span class="text-xs font-bold">
                                {{ completionDoneCount }}/{{
                                    completionChecks.length
                                }}
                            </span>
                        </div>
                        <div
                            class="mb-3 h-1.5 w-full overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                :style="{
                                    width: `${(completionDoneCount / completionChecks.length) * 100}%`,
                                }"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <div
                                v-for="check in completionChecks"
                                :key="check.label"
                                class="flex items-center gap-2"
                            >
                                <i
                                    :class="
                                        check.done
                                            ? 'pi pi-check-circle text-emerald-500'
                                            : 'pi pi-circle text-muted-foreground/40'
                                    "
                                    class="shrink-0 text-sm"
                                />
                                <span
                                    class="text-xs"
                                    :class="
                                        check.done
                                            ? 'text-foreground'
                                            : 'text-muted-foreground'
                                    "
                                    >{{ check.label }}</span
                                >
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- ─── Área de conteúdo ───────────────────────────── -->
                <div class="min-w-0 flex-1 flex flex-col gap-4">

                    <!-- ════════ EDITAL ════════ -->
                    <div
                        v-if="activeSection === 'edital'"
                        class="flex flex-col gap-5"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600"
                            >
                                <FileBadge :size="20" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold">
                                    Edital do Processo
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    Envie o edital oficial em PDF. Os candidatos
                                    poderão visualizá-lo na página do processo.
                                </p>
                            </div>
                        </div>

                        <!-- PDF salvo -->
                        <div
                            v-if="hasEditalPdf"
                            class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-emerald-200 bg-emerald-50/80 px-5 py-4 dark:border-emerald-900 dark:bg-emerald-950/40"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/20"
                                >
                                    <i
                                        class="pi pi-file-pdf text-xl text-emerald-600 dark:text-emerald-400"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-emerald-900 dark:text-emerald-100"
                                    >
                                        Edital em PDF salvo
                                    </p>
                                    <p
                                        class="text-xs text-emerald-700 dark:text-emerald-300"
                                    >
                                        Arquivo disponível para os candidatos
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a
                                    :href="
                                        selectionProcess.edital_download_url ??
                                        '#'
                                    "
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Button
                                        label="Ver edital"
                                        icon="pi pi-eye"
                                        size="small"
                                        type="button"
                                    />
                                </a>
                                <Button
                                    label="Excluir"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    outlined
                                    size="small"
                                    type="button"
                                    :disabled="editalForm.processing"
                                    @click="confirmRemoveEditalPdf"
                                />
                            </div>
                        </div>

                        <!-- Sem PDF -->
                        <div
                            v-else
                            class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed py-10 text-center"
                        >
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                            >
                                <FileBadge
                                    :size="26"
                                    class="text-muted-foreground"
                                />
                            </div>
                            <p class="text-sm font-medium">
                                Nenhum edital enviado
                            </p>
                            <p class="max-w-sm text-xs text-muted-foreground">
                                Envie o PDF do edital oficial para que os
                                candidatos possam consultá-lo durante a
                                inscrição.
                            </p>
                        </div>

                        <!-- Upload -->
                        <div
                            class="flex flex-col gap-4 rounded-xl border bg-card p-5"
                        >
                            <p class="text-sm font-semibold">
                                {{
                                    hasEditalPdf
                                        ? 'Substituir edital'
                                        : 'Enviar edital'
                                }}
                            </p>
                            <label
                                class="flex cursor-pointer flex-col gap-2 rounded-lg border border-dashed bg-muted/30 px-4 py-4 transition-colors hover:bg-muted/50"
                            >
                                <span
                                    class="text-center text-xs text-muted-foreground"
                                    >Clique para selecionar o PDF</span
                                >
                                <input
                                    :key="editalInputKey"
                                    type="file"
                                    accept="application/pdf,.pdf"
                                    class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-foreground"
                                    @change="onEditalFileChange"
                                />
                                <small
                                    v-if="editalForm.errors.edital"
                                    class="text-sm text-red-600"
                                    >{{ editalForm.errors.edital }}</small
                                >
                            </label>
                            <div class="flex justify-end">
                                <Button
                                    :label="
                                        hasEditalPdf
                                            ? 'Salvar novo edital'
                                            : 'Enviar edital'
                                    "
                                    icon="pi pi-upload"
                                    size="small"
                                    :loading="editalForm.processing"
                                    :disabled="!editalForm.edital"
                                    @click="submitEditalPdf"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ════════ DOCUMENTOS ════════ -->
                    <div
                        v-if="activeSection === 'documentos'"
                        class="flex flex-col gap-5"
                    >
                        <div
                            class="flex items-start justify-between gap-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <FileText :size="20" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold">
                                        Documentos Exigidos
                                    </h2>
                                    <p class="text-sm text-muted-foreground">
                                        Documentos que o candidato deve enviar
                                        para se inscrever.
                                    </p>
                                </div>
                            </div>
                            <Button
                                v-if="!showAddDocumentForm"
                                label="Adicionar"
                                icon="pi pi-plus"
                                size="small"
                                @click="showAddDocumentForm = true"
                            />
                            <Button
                                v-else
                                label="Cancelar"
                                icon="pi pi-times"
                                size="small"
                                outlined
                                severity="secondary"
                                @click="showAddDocumentForm = false"
                            />
                        </div>

                        <Message
                            v-if="props.selectionProcess.tipo_programa"
                            severity="info"
                            :closable="false"
                        >
                            A lista base de documentos é gerada
                            automaticamente conforme o tipo do programa
                            (Mestrado ou Doutorado). Você pode incluir
                            documentos adicionais ou remover itens do padrão.
                        </Message>

                        <!-- Formulário collapsível -->
                        <Transition name="slide-down">
                            <div
                                v-if="showAddDocumentForm"
                                class="rounded-xl border bg-muted/20 p-5"
                            >
                                <p class="mb-4 text-sm font-semibold">
                                    Novo documento exigido
                                </p>
                                <Fluid>
                                    <form
                                        class="flex flex-col gap-4"
                                        @submit.prevent="
                                            storeRequiredDocumentAction
                                        "
                                    >
                                        <label class="flex flex-col gap-1.5">
                                            <span class="text-sm">
                                                Tipo de Documento
                                                <span class="text-red-600"
                                                    >*</span
                                                >
                                            </span>
                                            <Select
                                                v-model="
                                                    requiredDocumentForm.tipo_documento_id
                                                "
                                                :options="props.tiposDocumento"
                                                option-label="descricao"
                                                option-value="id"
                                                placeholder="Selecione o documento"
                                                filter
                                                :invalid="
                                                    Boolean(
                                                        requiredDocumentForm
                                                            .errors
                                                            .tipo_documento_id,
                                                    )
                                                "
                                            />
                                            <small
                                                v-if="
                                                    requiredDocumentForm.errors
                                                        .tipo_documento_id
                                                "
                                                class="text-red-600"
                                                >{{
                                                    requiredDocumentForm.errors
                                                        .tipo_documento_id
                                                }}</small
                                            >
                                            <small
                                                v-else
                                                class="text-xs text-muted-foreground"
                                                >Ex.: RG, CPF, Foto 3x4,
                                                Comprovante de
                                                residência.</small
                                            >
                                        </label>

                                        <div
                                            class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_200px]"
                                        >
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Formatos aceitos</span
                                                >
                                                <InputText
                                                    v-model="
                                                        requiredDocumentForm.formatos_aceitos
                                                    "
                                                    placeholder="pdf, jpg, png"
                                                />
                                                <small
                                                    class="text-xs text-muted-foreground"
                                                    >Separados por
                                                    vírgula.</small
                                                >
                                            </label>
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Tamanho máximo</span
                                                >
                                                <InputNumber
                                                    v-model="
                                                        requiredDocumentForm.tamanho_max_mb
                                                    "
                                                    :min="1"
                                                    :max="100"
                                                    suffix=" MB"
                                                    show-buttons
                                                    fluid
                                                />
                                            </label>
                                        </div>

                                        <label class="flex flex-col gap-1.5">
                                            <span class="text-sm"
                                                >Observação para o
                                                candidato</span
                                            >
                                            <Textarea
                                                v-model="
                                                    requiredDocumentForm.descricao
                                                "
                                                rows="2"
                                                placeholder="Instruções específicas (opcional)"
                                            />
                                        </label>

                                        <div
                                            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-card px-4 py-3"
                                        >
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <ToggleSwitch
                                                    v-model="
                                                        requiredDocumentForm.obrigatorio
                                                    "
                                                />
                                                <span
                                                    class="text-sm font-medium"
                                                >
                                                    {{
                                                        requiredDocumentForm.obrigatorio
                                                            ? 'Documento obrigatório'
                                                            : 'Documento opcional'
                                                    }}
                                                </span>
                                            </div>
                                            <Button
                                                :fluid="false"
                                                type="submit"
                                                size="small"
                                                icon="pi pi-plus"
                                                label="Adicionar documento"
                                                :loading="
                                                    requiredDocumentForm.processing
                                                "
                                                :disabled="
                                                    !requiredDocumentForm.tipo_documento_id ||
                                                    requiredDocumentForm.processing
                                                "
                                            />
                                        </div>
                                    </form>
                                </Fluid>
                            </div>
                        </Transition>

                        <!-- Lista de documentos -->
                        <div class="overflow-hidden rounded-xl border">
                            <template
                                v-for="(doc, index) in props.selectionProcess
                                    .required_documents ?? []"
                                :key="doc.id"
                            >
                                <!-- Modo visualização -->
                                <div
                                    v-if="editingDocumentId !== doc.id"
                                    class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/30"
                                    :class="index > 0 ? 'border-t' : ''"
                                >
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10"
                                    >
                                        <FileText
                                            :size="14"
                                            class="text-primary"
                                        />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex flex-wrap items-center gap-1.5"
                                        >
                                            <p
                                                class="truncate text-sm font-medium"
                                            >
                                                {{
                                                    doc.tipo_documento
                                                        ?.descricao ??
                                                    'Não informado'
                                                }}
                                            </p>
                                            <Tag
                                                v-if="doc.gerado_por_template"
                                                value="Padrão"
                                                severity="secondary"
                                                class="text-xs"
                                            />
                                        </div>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                formatosToList(
                                                    doc.formatos_aceitos,
                                                )
                                                    .map((f) => f.toUpperCase())
                                                    .join(', ') || '—'
                                            }}
                                            · {{ doc.tamanho_max_mb }} MB
                                        </p>
                                    </div>
                                    <Tag
                                        :value="
                                            doc.obrigatorio
                                                ? 'Obrigatório'
                                                : 'Opcional'
                                        "
                                        :severity="
                                            doc.obrigatorio
                                                ? 'success'
                                                : 'secondary'
                                        "
                                        class="shrink-0 text-xs"
                                    />
                                    <Button
                                        v-tooltip.left="'Editar documento'"
                                        rounded
                                        text
                                        severity="secondary"
                                        icon="pi pi-pencil"
                                        size="small"
                                        aria-label="Editar documento"
                                        @click="openEditDocument(doc)"
                                    />
                                    <Button
                                        v-tooltip.left="'Remover documento'"
                                        rounded
                                        text
                                        severity="danger"
                                        icon="pi pi-trash"
                                        size="small"
                                        aria-label="Remover documento"
                                        @click="
                                            confirmRemoveRequiredDocument(doc)
                                        "
                                    />
                                </div>

                                <!-- Modo edição inline -->
                                <div
                                    v-else
                                    class="border-t bg-muted/10 p-4"
                                    :class="index === 0 ? '!border-t-0' : ''"
                                >
                                    <p
                                        class="mb-3 text-sm font-semibold text-primary"
                                    >
                                        Editando:
                                        {{
                                            doc.tipo_documento?.descricao ??
                                            'Documento'
                                        }}
                                    </p>
                                    <Fluid>
                                        <form
                                            class="flex flex-col gap-3"
                                            @submit.prevent="
                                                updateDocumentAction(doc.id)
                                            "
                                        >
                                            <div
                                                class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_200px]"
                                            >
                                                <label
                                                    class="flex flex-col gap-1.5"
                                                >
                                                    <span class="text-sm"
                                                        >Formatos aceitos</span
                                                    >
                                                    <InputText
                                                        v-model="
                                                            editDocumentForm.formatos_aceitos
                                                        "
                                                        placeholder="pdf, jpg, png"
                                                    />
                                                    <small
                                                        class="text-xs text-muted-foreground"
                                                        >Separados por
                                                        vírgula.</small
                                                    >
                                                </label>
                                                <label
                                                    class="flex flex-col gap-1.5"
                                                >
                                                    <span class="text-sm"
                                                        >Tamanho máximo</span
                                                    >
                                                    <InputNumber
                                                        v-model="
                                                            editDocumentForm.tamanho_max_mb
                                                        "
                                                        :min="1"
                                                        :max="100"
                                                        suffix=" MB"
                                                        show-buttons
                                                        fluid
                                                    />
                                                </label>
                                            </div>
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Observação para o
                                                    candidato</span
                                                >
                                                <Textarea
                                                    v-model="
                                                        editDocumentForm.descricao
                                                    "
                                                    rows="2"
                                                    placeholder="Instruções específicas (opcional)"
                                                />
                                            </label>
                                            <div
                                                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-card px-4 py-3"
                                            >
                                                <div
                                                    class="flex items-center gap-3"
                                                >
                                                    <ToggleSwitch
                                                        v-model="
                                                            editDocumentForm.obrigatorio
                                                        "
                                                    />
                                                    <span
                                                        class="text-sm font-medium"
                                                    >
                                                        {{
                                                            editDocumentForm.obrigatorio
                                                                ? 'Documento obrigatório'
                                                                : 'Documento opcional'
                                                        }}
                                                    </span>
                                                </div>
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <Button
                                                        :fluid="false"
                                                        type="button"
                                                        size="small"
                                                        icon="pi pi-times"
                                                        label="Cancelar"
                                                        severity="secondary"
                                                        outlined
                                                        @click="
                                                            cancelEditDocument
                                                        "
                                                    />
                                                    <Button
                                                        :fluid="false"
                                                        type="submit"
                                                        size="small"
                                                        icon="pi pi-check"
                                                        label="Salvar"
                                                        :loading="
                                                            editDocumentForm.processing
                                                        "
                                                    />
                                                </div>
                                            </div>
                                        </form>
                                    </Fluid>
                                </div>
                            </template>
                            <div
                                v-if="!requiredDocumentsCount"
                                class="flex flex-col items-center justify-center gap-2 py-12 text-center"
                            >
                                <i
                                    class="pi pi-file text-3xl text-muted-foreground"
                                />
                                <p class="text-sm font-medium">
                                    Nenhum documento cadastrado
                                </p>
                                <p
                                    class="max-w-xs text-xs text-muted-foreground"
                                >
                                    Clique em "Adicionar" para incluir os
                                    documentos exigidos na inscrição.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ TÍTULOS ════════ -->
                    <div
                        v-if="activeSection === 'titulos'"
                        class="flex flex-col gap-5"
                    >
                        <!-- Cabeçalho da seção -->
                        <div
                            class="flex items-start justify-between gap-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"
                                >
                                    <GraduationCap :size="20" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold">
                                        Títulos Aceitos
                                    </h2>
                                    <p class="text-sm text-muted-foreground">
                                        Configure grupos e itens do currículo
                                        para análise curricular.
                                    </p>
                                </div>
                            </div>
                            <Button
                                v-if="!showAddGroupForm"
                                label="Adicionar grupo"
                                icon="pi pi-plus"
                                size="small"
                                @click="showAddGroupForm = true"
                            />
                            <Button
                                v-else
                                label="Cancelar"
                                icon="pi pi-times"
                                size="small"
                                outlined
                                severity="secondary"
                                @click="showAddGroupForm = false"
                            />
                        </div>

                        <!-- Resumo da pontuação -->
                        <div
                            v-if="titleGroupsCount > 0"
                            class="flex flex-wrap items-center gap-4 rounded-xl border bg-amber-50/60 px-4 py-3 dark:bg-amber-950/20"
                        >
                            <div class="flex items-center gap-2">
                                <i
                                    class="pi pi-star-fill text-sm text-amber-500"
                                />
                                <span class="text-sm text-muted-foreground"
                                    >Pontuação máxima total:</span
                                >
                                <span class="text-sm font-bold"
                                    >{{
                                        formatScore(titleGroupsTotalMaxScore)
                                    }}
                                    pts</span
                                >
                            </div>
                            <div
                                class="flex items-center gap-2 border-l pl-4"
                            >
                                <span class="text-sm text-muted-foreground"
                                    >{{ titleGroupsCount }}
                                    {{
                                        titleGroupsCount === 1
                                            ? 'grupo'
                                            : 'grupos'
                                    }}
                                    configurado{{
                                        titleGroupsCount === 1 ? '' : 's'
                                    }}</span
                                >
                            </div>
                        </div>

                        <!-- Formulário de novo grupo (collapsível) -->
                        <Transition name="slide-down">
                            <div
                                v-if="showAddGroupForm"
                                class="rounded-xl border bg-muted/20 p-5"
                            >
                                <p class="mb-4 text-sm font-semibold">
                                    Novo grupo de títulos
                                </p>
                                <Fluid>
                                    <form
                                        class="flex flex-col gap-4"
                                        @submit.prevent="storeTitleGroupAction"
                                    >
                                        <div
                                            class="grid grid-cols-1 gap-4 md:grid-cols-[80px_1fr_160px]"
                                        >
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Código
                                                    <span class="text-red-600"
                                                        >*</span
                                                    ></span
                                                >
                                                <InputText
                                                    v-model="titleGroupForm.code"
                                                    placeholder="A"
                                                    :invalid="
                                                        Boolean(
                                                            titleGroupForm.errors
                                                                .code,
                                                        )
                                                    "
                                                />
                                                <small
                                                    v-if="
                                                        titleGroupForm.errors
                                                            .code
                                                    "
                                                    class="text-red-600"
                                                    >{{
                                                        titleGroupForm.errors
                                                            .code
                                                    }}</small
                                                >
                                            </label>
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Nome do grupo
                                                    <span class="text-red-600"
                                                        >*</span
                                                    ></span
                                                >
                                                <InputText
                                                    v-model="titleGroupForm.name"
                                                    placeholder="Ex.: Formação Acadêmica/Titulação"
                                                    :invalid="
                                                        Boolean(
                                                            titleGroupForm.errors
                                                                .name,
                                                        )
                                                    "
                                                />
                                                <small
                                                    v-if="
                                                        titleGroupForm.errors
                                                            .name
                                                    "
                                                    class="text-red-600"
                                                    >{{
                                                        titleGroupForm.errors
                                                            .name
                                                    }}</small
                                                >
                                            </label>
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Pontuação máxima
                                                    <span class="text-red-600"
                                                        >*</span
                                                    ></span
                                                >
                                                <InputNumber
                                                    v-model="
                                                        titleGroupForm.max_score
                                                    "
                                                    :min="0"
                                                    :max="9999.99"
                                                    :min-fraction-digits="1"
                                                    :max-fraction-digits="2"
                                                    placeholder="0,0"
                                                    :invalid="
                                                        Boolean(
                                                            titleGroupForm.errors
                                                                .max_score,
                                                        )
                                                    "
                                                    fluid
                                                />
                                                <small
                                                    v-if="
                                                        titleGroupForm.errors
                                                            .max_score
                                                    "
                                                    class="text-red-600"
                                                    >{{
                                                        titleGroupForm.errors
                                                            .max_score
                                                    }}</small
                                                >
                                            </label>
                                        </div>
                                        <label class="flex flex-col gap-1.5">
                                            <span class="text-sm"
                                                >Regras/Observações do
                                                grupo</span
                                            >
                                            <Textarea
                                                v-model="
                                                    titleGroupForm.description
                                                "
                                                rows="2"
                                                placeholder="Ex.: Serão considerados apenas os últimos 5 anos."
                                            />
                                        </label>
                                        <div class="flex justify-end">
                                            <Button
                                                :fluid="false"
                                                type="submit"
                                                size="small"
                                                icon="pi pi-plus"
                                                label="Adicionar grupo"
                                                :loading="
                                                    titleGroupForm.processing
                                                "
                                                :disabled="
                                                    !titleGroupForm.code ||
                                                    !titleGroupForm.name ||
                                                    titleGroupForm.processing
                                                "
                                            />
                                        </div>
                                    </form>
                                </Fluid>
                            </div>
                        </Transition>

                        <!-- Lista de grupos -->
                        <div
                            v-if="titleGroupsCount > 0"
                            class="flex flex-col gap-3"
                        >
                            <div
                                v-for="group in props.selectionProcess
                                    .title_groups ?? []"
                                :key="group.id"
                                class="overflow-hidden rounded-xl border"
                            >
                                <!-- Modo edição do grupo (inline) -->
                                <div
                                    v-if="editingGroupId === group.id"
                                    class="bg-muted/10 p-4"
                                >
                                    <p
                                        class="mb-3 text-sm font-semibold text-amber-700 dark:text-amber-400"
                                    >
                                        Editando grupo {{ group.code }}
                                    </p>
                                    <Fluid>
                                        <form
                                            class="flex flex-col gap-3"
                                            @submit.prevent="
                                                updateGroupAction(group.id)
                                            "
                                        >
                                            <div
                                                class="grid grid-cols-1 gap-3 md:grid-cols-[80px_1fr_160px]"
                                            >
                                                <label
                                                    class="flex flex-col gap-1.5"
                                                >
                                                    <span class="text-sm"
                                                        >Código
                                                        <span
                                                            class="text-red-600"
                                                            >*</span
                                                        ></span
                                                    >
                                                    <InputText
                                                        v-model="
                                                            editGroupForm.code
                                                        "
                                                        placeholder="A"
                                                        :invalid="
                                                            Boolean(
                                                                editGroupForm
                                                                    .errors
                                                                    .code,
                                                            )
                                                        "
                                                    />
                                                    <small
                                                        v-if="
                                                            editGroupForm.errors
                                                                .code
                                                        "
                                                        class="text-red-600"
                                                        >{{
                                                            editGroupForm.errors
                                                                .code
                                                        }}</small
                                                    >
                                                </label>
                                                <label
                                                    class="flex flex-col gap-1.5"
                                                >
                                                    <span class="text-sm"
                                                        >Nome do grupo
                                                        <span
                                                            class="text-red-600"
                                                            >*</span
                                                        ></span
                                                    >
                                                    <InputText
                                                        v-model="
                                                            editGroupForm.name
                                                        "
                                                        :invalid="
                                                            Boolean(
                                                                editGroupForm
                                                                    .errors
                                                                    .name,
                                                            )
                                                        "
                                                    />
                                                    <small
                                                        v-if="
                                                            editGroupForm.errors
                                                                .name
                                                        "
                                                        class="text-red-600"
                                                        >{{
                                                            editGroupForm.errors
                                                                .name
                                                        }}</small
                                                    >
                                                </label>
                                                <label
                                                    class="flex flex-col gap-1.5"
                                                >
                                                    <span class="text-sm"
                                                        >Pontuação máxima
                                                        <span
                                                            class="text-red-600"
                                                            >*</span
                                                        ></span
                                                    >
                                                    <InputNumber
                                                        v-model="
                                                            editGroupForm.max_score
                                                        "
                                                        :min="0"
                                                        :max="9999.99"
                                                        :min-fraction-digits="1"
                                                        :max-fraction-digits="2"
                                                        placeholder="0,0"
                                                        fluid
                                                    />
                                                </label>
                                            </div>
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Regras/Observações</span
                                                >
                                                <Textarea
                                                    v-model="
                                                        editGroupForm.description
                                                    "
                                                    rows="2"
                                                />
                                            </label>
                                            <div class="flex justify-end gap-2">
                                                <Button
                                                    :fluid="false"
                                                    type="button"
                                                    size="small"
                                                    icon="pi pi-times"
                                                    label="Cancelar"
                                                    severity="secondary"
                                                    outlined
                                                    @click="cancelEditGroup"
                                                />
                                                <Button
                                                    :fluid="false"
                                                    type="submit"
                                                    size="small"
                                                    icon="pi pi-check"
                                                    label="Salvar"
                                                    :loading="
                                                        editGroupForm.processing
                                                    "
                                                />
                                            </div>
                                        </form>
                                    </Fluid>
                                </div>

                                <!-- Cabeçalho do grupo (clicável) -->
                                <button
                                    v-else
                                    type="button"
                                    class="flex w-full items-center gap-3 px-4 py-3.5 text-left transition-colors hover:bg-muted/30"
                                    @click="toggleGroup(group.id)"
                                >
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-sm font-bold text-amber-700 dark:text-amber-400"
                                    >
                                        {{ group.code }}
                                    </span>
                                    <span
                                        class="flex-1 text-sm font-semibold"
                                        >{{ group.name }}</span
                                    >
                                    <div
                                        class="flex shrink-0 items-center gap-2"
                                    >
                                        <Tag
                                            :value="`Máx ${formatScore(group.max_score)} pts`"
                                            severity="warn"
                                            class="text-xs"
                                        />
                                        <Tag
                                            :value="`${group.items?.length ?? 0} ${(group.items?.length ?? 0) === 1 ? 'item' : 'itens'}`"
                                            severity="secondary"
                                            class="text-xs"
                                        />
                                        <Button
                                            v-tooltip.left="'Editar grupo'"
                                            rounded
                                            text
                                            severity="secondary"
                                            icon="pi pi-pencil"
                                            size="small"
                                            @click.stop="openEditGroup(group)"
                                        />
                                        <Button
                                            v-tooltip.left="'Remover grupo'"
                                            rounded
                                            text
                                            severity="danger"
                                            icon="pi pi-trash"
                                            size="small"
                                            @click.stop="
                                                confirmRemoveTitleGroup(group)
                                            "
                                        />
                                        <i
                                            :class="
                                                isGroupExpanded(group.id)
                                                    ? 'pi pi-chevron-up'
                                                    : 'pi pi-chevron-down'
                                            "
                                            class="text-xs text-muted-foreground"
                                        />
                                    </div>
                                </button>

                                <!-- Corpo do grupo (expansível) -->
                                <div
                                    v-if="isGroupExpanded(group.id)"
                                    class="border-t"
                                >
                                    <!-- Regras do grupo -->
                                    <div
                                        v-if="group.description"
                                        class="border-b bg-muted/10 px-4 py-2.5"
                                    >
                                        <p
                                            class="text-xs italic text-muted-foreground"
                                        >
                                            {{ group.description }}
                                        </p>
                                    </div>

                                    <!-- Itens do grupo -->
                                    <template
                                        v-for="(item, idx) in group.items ??
                                        []"
                                        :key="item.id"
                                    >
                                        <!-- Modo edição do item (inline) -->
                                        <div
                                            v-if="editingItemId === item.id"
                                            class="border-t bg-muted/10 p-4"
                                            :class="idx === 0 ? '!border-t-0' : ''"
                                        >
                                            <p
                                                class="mb-3 text-sm font-semibold text-amber-700 dark:text-amber-400"
                                            >
                                                Editando item {{ item.code }}
                                            </p>
                                            <Fluid>
                                                <form
                                                    class="flex flex-col gap-3"
                                                    @submit.prevent="
                                                        updateItemAction(
                                                            group.id,
                                                            item.id,
                                                        )
                                                    "
                                                >
                                                    <div
                                                        class="grid grid-cols-1 gap-3 md:grid-cols-[100px_1fr]"
                                                    >
                                                        <label
                                                            class="flex flex-col gap-1.5"
                                                        >
                                                            <span
                                                                class="text-sm"
                                                                >Código
                                                                <span
                                                                    class="text-red-600"
                                                                    >*</span
                                                                ></span
                                                            >
                                                            <InputText
                                                                v-model="
                                                                    editItemForm.code
                                                                "
                                                                :invalid="
                                                                    Boolean(
                                                                        editItemForm
                                                                            .errors
                                                                            .code,
                                                                    )
                                                                "
                                                            />
                                                        </label>
                                                        <label
                                                            class="flex flex-col gap-1.5"
                                                        >
                                                            <span
                                                                class="text-sm"
                                                                >Título/Descrição
                                                                <span
                                                                    class="text-red-600"
                                                                    >*</span
                                                                ></span
                                                            >
                                                            <InputText
                                                                v-model="
                                                                    editItemForm.title
                                                                "
                                                                :invalid="
                                                                    Boolean(
                                                                        editItemForm
                                                                            .errors
                                                                            .title,
                                                                    )
                                                                "
                                                            />
                                                        </label>
                                                    </div>
                                                    <div
                                                        class="grid grid-cols-1 gap-3 md:grid-cols-3"
                                                    >
                                                        <label
                                                            class="flex flex-col gap-1.5"
                                                        >
                                                            <span
                                                                class="text-sm"
                                                                >Pontos/unidade
                                                                <span
                                                                    class="text-red-600"
                                                                    >*</span
                                                                ></span
                                                            >
                                                            <InputNumber
                                                                v-model="
                                                                    editItemForm.score_per_unit
                                                                "
                                                                :min="0"
                                                                :max="9999.99"
                                                                :min-fraction-digits="1"
                                                                :max-fraction-digits="2"
                                                                fluid
                                                            />
                                                        </label>
                                                        <label
                                                            class="flex flex-col gap-1.5"
                                                        >
                                                            <span
                                                                class="text-sm"
                                                                >Unidade
                                                                <span
                                                                    class="text-red-600"
                                                                    >*</span
                                                                ></span
                                                            >
                                                            <InputText
                                                                v-model="
                                                                    editItemForm.score_unit
                                                                "
                                                                placeholder="por título"
                                                            />
                                                        </label>
                                                        <label
                                                            class="flex flex-col gap-1.5"
                                                        >
                                                            <span
                                                                class="text-sm"
                                                                >Qtd. máxima</span
                                                            >
                                                            <InputNumber
                                                                v-model="
                                                                    editItemForm.max_quantity
                                                                "
                                                                :min="1"
                                                                :max="9999"
                                                                fluid
                                                            />
                                                        </label>
                                                    </div>
                                                    <label
                                                        class="flex flex-col gap-1.5"
                                                    >
                                                        <span class="text-sm"
                                                            >Regra de
                                                            período</span
                                                        >
                                                        <InputText
                                                            v-model="
                                                                editItemForm.period_rule
                                                            "
                                                            placeholder="Ex.: últimos 5 anos"
                                                        />
                                                    </label>
                                                    <div
                                                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-card px-4 py-3"
                                                    >
                                                        <div
                                                            class="flex items-center gap-3"
                                                        >
                                                            <ToggleSwitch
                                                                v-model="
                                                                    editItemForm.requires_attachment
                                                                "
                                                            />
                                                            <span
                                                                class="text-sm font-medium"
                                                                >Exige
                                                                comprovante</span
                                                            >
                                                        </div>
                                                        <div
                                                            class="flex items-center gap-2"
                                                        >
                                                            <Button
                                                                :fluid="false"
                                                                type="button"
                                                                size="small"
                                                                icon="pi pi-times"
                                                                label="Cancelar"
                                                                severity="secondary"
                                                                outlined
                                                                @click="
                                                                    cancelEditItem
                                                                "
                                                            />
                                                            <Button
                                                                :fluid="false"
                                                                type="submit"
                                                                size="small"
                                                                icon="pi pi-check"
                                                                label="Salvar"
                                                                :loading="
                                                                    editItemForm.processing
                                                                "
                                                            />
                                                        </div>
                                                    </div>
                                                </form>
                                            </Fluid>
                                        </div>

                                        <!-- Modo visualização do item -->
                                        <div
                                            v-else
                                            class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-muted/20"
                                            :class="idx > 0 ? 'border-t' : ''"
                                        >
                                            <span
                                                class="flex shrink-0 items-center justify-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-700 dark:text-amber-400"
                                            >
                                                {{ item.code }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="text-sm font-medium leading-snug"
                                                >
                                                    {{ item.title }}
                                                </p>
                                                <div
                                                    class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                                                >
                                                    <span
                                                        class="font-semibold text-amber-700 dark:text-amber-400"
                                                        >{{
                                                            formatScore(
                                                                item.score_per_unit,
                                                            )
                                                        }}
                                                        pt
                                                        {{
                                                            item.score_unit
                                                        }}</span
                                                    >
                                                    <span
                                                        v-if="item.max_quantity"
                                                        >· máx
                                                        {{ item.max_quantity }}
                                                        unidades</span
                                                    >
                                                    <span
                                                        v-if="item.period_rule"
                                                        >·
                                                        {{
                                                            item.period_rule
                                                        }}</span
                                                    >
                                                </div>
                                            </div>
                                            <div
                                                class="flex shrink-0 items-center gap-1"
                                            >
                                                <Tag
                                                    v-if="
                                                        item.requires_attachment
                                                    "
                                                    value="Comprovante"
                                                    severity="info"
                                                    class="text-xs"
                                                />
                                                <Button
                                                    v-tooltip.left="
                                                        'Editar item'
                                                    "
                                                    rounded
                                                    text
                                                    severity="secondary"
                                                    icon="pi pi-pencil"
                                                    size="small"
                                                    @click="openEditItem(item)"
                                                />
                                                <Button
                                                    v-tooltip.left="
                                                        'Remover item'
                                                    "
                                                    rounded
                                                    text
                                                    severity="danger"
                                                    icon="pi pi-trash"
                                                    size="small"
                                                    @click="
                                                        confirmRemoveTitleItem(
                                                            group,
                                                            item,
                                                        )
                                                    "
                                                />
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Estado vazio dos itens -->
                                    <div
                                        v-if="!group.items?.length"
                                        class="px-4 py-6 text-center"
                                    >
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Nenhum item cadastrado neste grupo.
                                        </p>
                                    </div>

                                    <!-- Área de adicionar item -->
                                    <div
                                        class="border-t bg-muted/10 px-4 py-3"
                                    >
                                        <!-- Formulário de novo item -->
                                        <Transition name="slide-down">
                                            <div
                                                v-if="
                                                    showAddItemFormForGroup ===
                                                    group.id
                                                "
                                                class="mb-3 rounded-lg border bg-card p-4"
                                            >
                                                <p
                                                    class="mb-3 text-sm font-semibold"
                                                >
                                                    Novo item — grupo
                                                    {{ group.code }}
                                                </p>
                                                <Fluid>
                                                    <form
                                                        class="flex flex-col gap-3"
                                                        @submit.prevent="
                                                            storeTitleItemAction
                                                        "
                                                    >
                                                        <div
                                                            class="grid grid-cols-1 gap-3 md:grid-cols-[100px_1fr]"
                                                        >
                                                            <label
                                                                class="flex flex-col gap-1.5"
                                                            >
                                                                <span
                                                                    class="text-sm"
                                                                    >Código
                                                                    <span
                                                                        class="text-red-600"
                                                                        >*</span
                                                                    ></span
                                                                >
                                                                <InputText
                                                                    v-model="
                                                                        titleItemForm.code
                                                                    "
                                                                    :placeholder="`${group.code}.1`"
                                                                    :invalid="
                                                                        Boolean(
                                                                            titleItemForm
                                                                                .errors
                                                                                .code,
                                                                        )
                                                                    "
                                                                />
                                                                <small
                                                                    v-if="
                                                                        titleItemForm
                                                                            .errors
                                                                            .code
                                                                    "
                                                                    class="text-red-600"
                                                                    >{{
                                                                        titleItemForm
                                                                            .errors
                                                                            .code
                                                                    }}</small
                                                                >
                                                            </label>
                                                            <label
                                                                class="flex flex-col gap-1.5"
                                                            >
                                                                <span
                                                                    class="text-sm"
                                                                    >Descrição
                                                                    do título
                                                                    <span
                                                                        class="text-red-600"
                                                                        >*</span
                                                                    ></span
                                                                >
                                                                <Textarea
                                                                    v-model="
                                                                        titleItemForm.title
                                                                    "
                                                                    rows="2"
                                                                    placeholder="Ex.: Certificado de Especialista em Saúde Pública..."
                                                                    :invalid="
                                                                        Boolean(
                                                                            titleItemForm
                                                                                .errors
                                                                                .title,
                                                                        )
                                                                    "
                                                                />
                                                                <small
                                                                    v-if="
                                                                        titleItemForm
                                                                            .errors
                                                                            .title
                                                                    "
                                                                    class="text-red-600"
                                                                    >{{
                                                                        titleItemForm
                                                                            .errors
                                                                            .title
                                                                    }}</small
                                                                >
                                                            </label>
                                                        </div>
                                                        <div
                                                            class="grid grid-cols-1 gap-3 md:grid-cols-3"
                                                        >
                                                            <label
                                                                class="flex flex-col gap-1.5"
                                                            >
                                                                <span
                                                                    class="text-sm"
                                                                    >Pontuação
                                                                    por unidade
                                                                    <span
                                                                        class="text-red-600"
                                                                        >*</span
                                                                    ></span
                                                                >
                                                                <InputNumber
                                                                    v-model="
                                                                        titleItemForm.score_per_unit
                                                                    "
                                                                    :min="0"
                                                                    :max="
                                                                        9999.99
                                                                    "
                                                                    :min-fraction-digits="
                                                                        2
                                                                    "
                                                                    :max-fraction-digits="
                                                                        2
                                                                    "
                                                                    :invalid="
                                                                        Boolean(
                                                                            titleItemForm
                                                                                .errors
                                                                                .score_per_unit,
                                                                        )
                                                                    "
                                                                    fluid
                                                                />
                                                                <small
                                                                    v-if="
                                                                        titleItemForm
                                                                            .errors
                                                                            .score_per_unit
                                                                    "
                                                                    class="text-red-600"
                                                                    >{{
                                                                        titleItemForm
                                                                            .errors
                                                                            .score_per_unit
                                                                    }}</small
                                                                >
                                                            </label>
                                                            <label
                                                                class="flex flex-col gap-1.5"
                                                            >
                                                                <span
                                                                    class="text-sm"
                                                                    >Unidade
                                                                    <span
                                                                        class="text-red-600"
                                                                        >*</span
                                                                    ></span
                                                                >
                                                                <InputText
                                                                    v-model="
                                                                        titleItemForm.score_unit
                                                                    "
                                                                    placeholder="por título, por ano..."
                                                                    :invalid="
                                                                        Boolean(
                                                                            titleItemForm
                                                                                .errors
                                                                                .score_unit,
                                                                        )
                                                                    "
                                                                />
                                                                <small
                                                                    v-if="
                                                                        titleItemForm
                                                                            .errors
                                                                            .score_unit
                                                                    "
                                                                    class="text-red-600"
                                                                    >{{
                                                                        titleItemForm
                                                                            .errors
                                                                            .score_unit
                                                                    }}</small
                                                                >
                                                                <small
                                                                    v-else
                                                                    class="text-xs text-muted-foreground"
                                                                    >Ex.: por
                                                                    título, por
                                                                    ano, por
                                                                    semestre</small
                                                                >
                                                            </label>
                                                            <label
                                                                class="flex flex-col gap-1.5"
                                                            >
                                                                <span
                                                                    class="text-sm"
                                                                    >Qtd.
                                                                    máxima</span
                                                                >
                                                                <InputNumber
                                                                    v-model="
                                                                        titleItemForm.max_quantity
                                                                    "
                                                                    :min="1"
                                                                    :max="9999"
                                                                    show-buttons
                                                                    placeholder="Sem limite"
                                                                    fluid
                                                                />
                                                                <small
                                                                    class="text-xs text-muted-foreground"
                                                                    >Em branco =
                                                                    sem
                                                                    limite</small
                                                                >
                                                            </label>
                                                        </div>
                                                        <label
                                                            class="flex flex-col gap-1.5"
                                                        >
                                                            <span
                                                                class="text-sm"
                                                                >Regra de
                                                                período</span
                                                            >
                                                            <InputText
                                                                v-model="
                                                                    titleItemForm.period_rule
                                                                "
                                                                placeholder="Ex.: últimos 5 anos"
                                                            />
                                                        </label>
                                                        <label
                                                            class="flex flex-col gap-1.5"
                                                        >
                                                            <span
                                                                class="text-sm"
                                                                >Instrução para
                                                                o candidato</span
                                                            >
                                                            <Textarea
                                                                v-model="
                                                                    titleItemForm.candidate_instructions
                                                                "
                                                                rows="2"
                                                                placeholder="Instruções sobre o comprovante (opcional)"
                                                            />
                                                        </label>
                                                        <div
                                                            class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_200px]"
                                                        >
                                                            <label
                                                                class="flex flex-col gap-1.5"
                                                            >
                                                                <span
                                                                    class="text-sm"
                                                                    >Formatos
                                                                    aceitos</span
                                                                >
                                                                <InputText
                                                                    v-model="
                                                                        titleItemForm.accepted_formats
                                                                    "
                                                                    placeholder="pdf, jpg, png"
                                                                />
                                                                <small
                                                                    class="text-xs text-muted-foreground"
                                                                    >Separados
                                                                    por
                                                                    vírgula.</small
                                                                >
                                                            </label>
                                                            <label
                                                                class="flex flex-col gap-1.5"
                                                            >
                                                                <span
                                                                    class="text-sm"
                                                                    >Tamanho
                                                                    máximo</span
                                                                >
                                                                <InputNumber
                                                                    v-model="
                                                                        titleItemForm.max_file_size_mb
                                                                    "
                                                                    :min="1"
                                                                    :max="100"
                                                                    suffix=" MB"
                                                                    show-buttons
                                                                    fluid
                                                                />
                                                            </label>
                                                        </div>
                                                        <div
                                                            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-muted/30 px-4 py-3"
                                                        >
                                                            <div
                                                                class="flex items-center gap-3"
                                                            >
                                                                <ToggleSwitch
                                                                    v-model="
                                                                        titleItemForm.requires_attachment
                                                                    "
                                                                />
                                                                <span
                                                                    class="text-sm font-medium"
                                                                    >{{
                                                                        titleItemForm.requires_attachment
                                                                            ? 'Exige comprovante'
                                                                            : 'Sem comprovante'
                                                                    }}</span
                                                                >
                                                            </div>
                                                            <div
                                                                class="flex gap-2"
                                                            >
                                                                <Button
                                                                    :fluid="
                                                                        false
                                                                    "
                                                                    outlined
                                                                    severity="secondary"
                                                                    size="small"
                                                                    icon="pi pi-times"
                                                                    label="Cancelar"
                                                                    @click="
                                                                        closeAddItemForm
                                                                    "
                                                                />
                                                                <Button
                                                                    :fluid="
                                                                        false
                                                                    "
                                                                    type="submit"
                                                                    size="small"
                                                                    icon="pi pi-plus"
                                                                    label="Adicionar item"
                                                                    :loading="
                                                                        titleItemForm.processing
                                                                    "
                                                                    :disabled="
                                                                        !titleItemForm.code ||
                                                                        !titleItemForm.title ||
                                                                        !titleItemForm.score_unit ||
                                                                        titleItemForm.processing
                                                                    "
                                                                />
                                                            </div>
                                                        </div>
                                                    </form>
                                                </Fluid>
                                            </div>
                                        </Transition>

                                        <Button
                                            v-if="
                                                showAddItemFormForGroup !==
                                                group.id
                                            "
                                            label="Adicionar item"
                                            icon="pi pi-plus"
                                            size="small"
                                            text
                                            class="text-xs"
                                            @click="openAddItemForm(group.id)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado vazio -->
                        <div
                            v-if="!titleGroupsCount"
                            class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed py-12 text-center"
                        >
                            <i
                                class="pi pi-graduation-cap text-3xl text-muted-foreground"
                            />
                            <p class="text-sm font-medium">
                                Nenhum grupo configurado
                            </p>
                            <p class="max-w-xs text-xs text-muted-foreground">
                                Clique em "Adicionar grupo" para criar os grupos
                                do currículo (Ex.: A. Formação Acadêmica, B.
                                Atuação Profissional).
                            </p>
                        </div>
                    </div>

                    <!-- ════════ CRITÉRIOS ════════ -->
                    <div
                        v-if="activeSection === 'criterios'"
                        class="flex flex-col gap-5"
                    >
                        <div
                            class="flex items-start justify-between gap-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600"
                                >
                                    <Award :size="20" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold">
                                        Critérios de Avaliação
                                    </h2>
                                    <p class="text-sm text-muted-foreground">
                                        Defina os critérios e pesos utilizados
                                        na avaliação dos candidatos.
                                    </p>
                                </div>
                            </div>
                            <Button
                                v-if="!showAddCriteriaForm"
                                label="Adicionar"
                                icon="pi pi-plus"
                                size="small"
                                @click="showAddCriteriaForm = true"
                            />
                            <Button
                                v-else
                                label="Cancelar"
                                icon="pi pi-times"
                                size="small"
                                outlined
                                severity="secondary"
                                @click="showAddCriteriaForm = false"
                            />
                        </div>

                        <!-- Formulário collapsível -->
                        <Transition name="slide-down">
                            <div
                                v-if="showAddCriteriaForm"
                                class="rounded-xl border bg-muted/20 p-5"
                            >
                                <p class="mb-4 text-sm font-semibold">
                                    Novo critério
                                </p>
                                <Fluid>
                                    <form
                                        class="flex flex-col gap-4"
                                        @submit.prevent="storeCriteriaAction"
                                    >
                                        <label class="flex flex-col gap-1.5">
                                            <span class="text-sm">
                                                Nome do critério
                                                <span class="text-red-600"
                                                    >*</span
                                                >
                                            </span>
                                            <InputText
                                                v-model="criteriaForm.nome"
                                                placeholder="Ex.: Análise de currículo"
                                                :invalid="
                                                    Boolean(
                                                        criteriaForm.errors.nome,
                                                    )
                                                "
                                            />
                                            <small
                                                v-if="criteriaForm.errors.nome"
                                                class="text-red-600"
                                                >{{
                                                    criteriaForm.errors.nome
                                                }}</small
                                            >
                                        </label>
                                        <div
                                            class="grid grid-cols-1 gap-4 md:grid-cols-3"
                                        >
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Peso</span
                                                >
                                                <InputNumber
                                                    v-model="criteriaForm.peso"
                                                    :min="0.1"
                                                    :max="100"
                                                    :min-fraction-digits="1"
                                                    :max-fraction-digits="2"
                                                    placeholder="Peso"
                                                    fluid
                                                />
                                            </label>
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Pontuação máxima</span
                                                >
                                                <InputNumber
                                                    v-model="
                                                        criteriaForm.pontuacao_max
                                                    "
                                                    :min="1"
                                                    :max="1000"
                                                    :min-fraction-digits="0"
                                                    :max-fraction-digits="2"
                                                    placeholder="Máx."
                                                    fluid
                                                />
                                            </label>
                                            <label
                                                class="flex flex-col gap-1.5"
                                            >
                                                <span class="text-sm"
                                                    >Ordem</span
                                                >
                                                <InputNumber
                                                    v-model="criteriaForm.ordem"
                                                    :min="1"
                                                    :max="999"
                                                    show-buttons
                                                    placeholder="Ordem"
                                                    fluid
                                                />
                                            </label>
                                        </div>
                                        <div class="flex justify-end">
                                            <Button
                                                :fluid="false"
                                                type="submit"
                                                size="small"
                                                icon="pi pi-check"
                                                label="Salvar critério"
                                                :loading="
                                                    criteriaForm.processing
                                                "
                                                :disabled="
                                                    !criteriaForm.nome ||
                                                    criteriaForm.processing
                                                "
                                            />
                                        </div>
                                    </form>
                                </Fluid>
                            </div>
                        </Transition>

                        <!-- Lista de critérios -->
                        <div class="overflow-hidden rounded-xl border">
                            <div
                                v-for="(criteria, index) in props
                                    .selectionProcess.criteria ?? []"
                                :key="criteria.id"
                                class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/30"
                                :class="index > 0 ? 'border-t' : ''"
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-xs font-bold text-emerald-700 dark:text-emerald-400"
                                >
                                    {{ criteria.ordem }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium">
                                        {{ criteria.nome }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Peso {{ criteria.peso }} · Máx.
                                        {{ criteria.pontuacao_max }} pts
                                    </p>
                                </div>
                                <Button
                                    v-tooltip.left="'Remover critério'"
                                    rounded
                                    text
                                    severity="danger"
                                    icon="pi pi-trash"
                                    size="small"
                                    aria-label="Remover critério"
                                    @click="deleteCriteriaAction(criteria.id)"
                                />
                            </div>
                            <div
                                v-if="!criteriaCount"
                                class="flex flex-col items-center justify-center gap-2 py-12 text-center"
                            >
                                <i
                                    class="pi pi-list text-3xl text-muted-foreground"
                                />
                                <p class="text-sm font-medium">
                                    Nenhum critério cadastrado
                                </p>
                                <p
                                    class="max-w-xs text-xs text-muted-foreground"
                                >
                                    Clique em "Adicionar" para definir os
                                    critérios de avaliação.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ ETAPAS ════════ -->
                    <div
                        v-if="activeSection === 'etapas'"
                        class="flex flex-col gap-5"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600"
                            >
                                <Layers :size="20" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold">
                                    Etapas do Processo
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    Sequência de etapas configuradas para este
                                    processo seletivo.
                                </p>
                            </div>
                        </div>

                        <!-- Lista de etapas -->
                        <div class="overflow-hidden rounded-xl border">
                            <div
                                v-for="(stage, index) in props.selectionProcess
                                    .stages ?? []"
                                :key="stage.id"
                                class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/30"
                                :class="index > 0 ? 'border-t' : ''"
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-500/10 text-xs font-bold text-violet-700 dark:text-violet-400"
                                >
                                    {{ stage.ordem }}
                                </span>
                                <p class="text-sm font-medium">
                                    {{ stage.nome }}
                                </p>
                            </div>
                            <div
                                v-if="!stagesCount"
                                class="flex flex-col items-center justify-center gap-2 py-12 text-center"
                            >
                                <i
                                    class="pi pi-sitemap text-3xl text-muted-foreground"
                                />
                                <p class="text-sm font-medium">
                                    Nenhuma etapa cadastrada
                                </p>
                                <p
                                    class="max-w-xs text-xs text-muted-foreground"
                                >
                                    As etapas são gerenciadas na edição do
                                    processo.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- ─── fim conteúdo ──────────────────────────────── -->

            </div>
            <!-- ─── fim layout principal ──────────────────────────── -->

        </div>
    </div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition:
        opacity 0.2s ease,
        max-height 0.25s ease;
    overflow: hidden;
    max-height: 900px;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}
</style>
