<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarRange,
    FileBadge,
    FileText,
    GraduationCap,
    Layers,
    Settings2,
    Users,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Divider from 'primevue/divider';
import Fieldset from 'primevue/fieldset';
import Fluid from 'primevue/fluid';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/admin/processes';
import {
    destroy as destroyCriteria,
    store as storeCriteria,
} from '@/routes/admin/processes/criteria';
import {
    destroy as destroyRequiredDocument,
    store as storeRequiredDocument,
} from '@/routes/admin/processes/required-documents';
import {
    destroy as destroyRequiredTitulo,
    store as storeRequiredTitulo,
} from '@/routes/admin/processes/required-titulos';

type SelectionProcess = {
    id: number;
    titulo: string;
    descricao?: string | null;
    regras?: string | null;
    status: string;
    inscricao_inicio_em?: string | null;
    inscricao_fim_em?: string | null;
    stages?: Array<{ id: number; nome: string; ordem: number }>;
    required_documents?: Array<{
        id: number;
        tipo_documento_id?: number | null;
        nome: string;
        descricao?: string | null;
        formatos_aceitos?: string[] | null;
        tamanho_max_mb: number;
        obrigatorio: boolean;
        tipo_documento?: { id: number; descricao: string } | null;
    }>;
    required_titulos?: Array<{
        id: number;
        tipo_titulo_id: number;
        pontuacao_max: string | number;
        qtd_maxima?: number | null;
        obrigatorio: boolean;
        formatos_aceitos?: string[] | null;
        tamanho_max_mb: number;
        descricao?: string | null;
        tipo_titulo?: {
            id: number;
            descricao: string;
            calculo?: string | null;
        } | null;
    }>;
    criteria?: Array<{
        id: number;
        nome: string;
        peso: number;
        ordem: number;
        pontuacao_max: number;
    }>;
    evaluator_assignments?: Array<{ id: number }>;
};

const props = defineProps<{
    selectionProcess: SelectionProcess;
    tiposDocumento: Array<{ id: number; descricao: string }>;
    tiposTitulo: Array<{
        id: number;
        descricao: string;
        calculo?: string | null;
    }>;
}>();

const vTooltip = Tooltip;
const confirm = useConfirm();

const requiredDocumentForm = useForm({
    tipo_documento_id: null as number | null,
    descricao: '',
    formatos_aceitos: 'pdf,jpg,png',
    tamanho_max_mb: 10,
    obrigatorio: true,
});

const requiredTituloForm = useForm({
    tipo_titulo_id: null as number | null,
    pontuacao_max: 10,
    qtd_maxima: null as number | null,
    obrigatorio: false,
    formatos_aceitos: 'pdf,jpg,png',
    tamanho_max_mb: 10,
    descricao: '',
});

const criteriaForm = useForm({
    nome: '',
    peso: 1,
    pontuacao_max: 10,
    ordem: 1,
});

const statusSeverity: Record<
    string,
    'secondary' | 'success' | 'warn' | 'danger'
> = {
    rascunho: 'secondary',
    ativo: 'success',
    encerrado: 'warn',
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

const requiredDocumentsCount = computed(
    () => props.selectionProcess.required_documents?.length ?? 0,
);
const requiredTitulosCount = computed(
    () => props.selectionProcess.required_titulos?.length ?? 0,
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

type ConfigStepKey = 'documentos' | 'titulos';

const configSteps = computed<
    Array<{ key: ConfigStepKey; label: string; count: number }>
>(() => [
    {
        key: 'documentos',
        label: 'Documentos exigidos',
        count: requiredDocumentsCount.value,
    },
    {
        key: 'titulos',
        label: 'Títulos aceitos',
        count: requiredTitulosCount.value,
    },
]);

const activeConfigStep = ref<ConfigStepKey>('documentos');

const calculoLabel = (calculo?: string | null): string => {
    if (calculo === 'data') {
        return 'Por data';
    }
    if (calculo === 'valor') {
        return 'Por valor';
    }
    return '-';
};

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
            },
        },
    );
};

const storeRequiredTituloAction = (): void => {
    requiredTituloForm.post(
        storeRequiredTitulo(props.selectionProcess.id).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                requiredTituloForm.reset();
                requiredTituloForm.pontuacao_max = 10;
                requiredTituloForm.formatos_aceitos = 'pdf,jpg,png';
                requiredTituloForm.tamanho_max_mb = 10;
                requiredTituloForm.obrigatorio = false;
            },
        },
    );
};

const storeCriteriaAction = (): void => {
    criteriaForm.post(storeCriteria(props.selectionProcess.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            criteriaForm.reset();
            criteriaForm.peso = 1;
            criteriaForm.pontuacao_max = 10;
            criteriaForm.ordem = 1;
        },
    });
};

const deleteRequiredDocumentAction = (documentId: number): void => {
    router.delete(
        destroyRequiredDocument({
            selectionProcess: props.selectionProcess.id,
            processRequiredDocument: documentId,
        }).url,
        { preserveScroll: true },
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
        accept: () => deleteRequiredDocumentAction(doc.id),
    });
};

const deleteRequiredTituloAction = (tituloId: number): void => {
    router.delete(
        destroyRequiredTitulo({
            selectionProcess: props.selectionProcess.id,
            processRequiredTitulo: tituloId,
        }).url,
        { preserveScroll: true },
    );
};

const confirmRemoveRequiredTitulo = (titulo: {
    id: number;
    tipo_titulo?: { descricao: string } | null;
}): void => {
    const tituloName = titulo.tipo_titulo?.descricao ?? 'título';
    confirm.require({
        header: 'Remover título aceito',
        message: `Deseja remover o título "${tituloName}" deste processo?`,
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Remover',
        rejectProps: { outlined: true, icon: 'pi pi-times' },
        acceptProps: { severity: 'danger', icon: 'pi pi-trash' },
        accept: () => deleteRequiredTituloAction(titulo.id),
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
</script>

<template>
    <div class="p-1">
        <ConfirmDialog />

        <div class="mx-auto flex w-full max-w-[1520px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    :title="`Configuração: ${props.selectionProcess.titulo}`"
                    description="Configure documentos exigidos, títulos aceitos, critérios e etapas do processo."
                    :icon="Settings2"
                />
                <Link :href="edit(props.selectionProcess.id).url">
                    <Button
                        label="Editar processo"
                        icon="pi pi-pencil"
                        size="small"
                    />
                </Link>
            </div>

            <Card class="rounded-xl shadow-md">
                <template #content>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div
                            class="flex items-start gap-3 rounded-xl border p-4"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <FileText :size="20" />
                            </div>
                            <div class="flex flex-col">
                                <p class="text-xs text-muted-foreground">
                                    Status
                                </p>
                                <Tag
                                    class="mt-1 self-start"
                                    :value="props.selectionProcess.status"
                                    :severity="
                                        statusSeverity[
                                            props.selectionProcess.status
                                        ] ?? 'secondary'
                                    "
                                />
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-3 rounded-xl border p-4"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600"
                            >
                                <CalendarRange :size="20" />
                            </div>
                            <div class="flex flex-col">
                                <p class="text-xs text-muted-foreground">
                                    Período de inscrição
                                </p>
                                <p
                                    class="mt-1 text-sm font-semibold leading-tight"
                                >
                                    {{ periodLabel }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-3 rounded-xl border p-4"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600"
                            >
                                <Layers :size="20" />
                            </div>
                            <div class="flex flex-col">
                                <p class="text-xs text-muted-foreground">
                                    Etapas
                                </p>
                                <p class="mt-1 text-2xl font-semibold">
                                    {{ stagesCount }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-3 rounded-xl border p-4"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600"
                            >
                                <Users :size="20" />
                            </div>
                            <div class="flex flex-col">
                                <p class="text-xs text-muted-foreground">
                                    Avaliadores vinculados
                                </p>
                                <p class="mt-1 text-2xl font-semibold">
                                    {{ evaluatorsCount }}
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="rounded-xl shadow-md">
                <template #content>
                    <div class="flex flex-col gap-4 p-2 md:p-3">
                        <div class="flex flex-col gap-1">
                            <h3 class="text-base font-semibold">
                                Etapas da configuração
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Configure os itens em sequência para montar o
                                processo seletivo.
                            </p>
                        </div>
                        <div class="grid gap-2 md:grid-cols-2">
                            <button
                                v-for="step in configSteps"
                                :key="step.key"
                                type="button"
                                class="flex items-center justify-between rounded-lg border px-3 py-2 text-left transition-colors"
                                :class="
                                    activeConfigStep === step.key
                                        ? 'border-primary bg-primary/5 text-primary'
                                        : 'border-border hover:bg-muted/40'
                                "
                                @click="activeConfigStep = step.key"
                            >
                                <span class="text-sm font-medium">
                                    {{ step.label }}
                                </span>
                                <Tag
                                    :value="String(step.count)"
                                    :severity="
                                        activeConfigStep === step.key
                                            ? 'contrast'
                                            : 'secondary'
                                    "
                                />
                            </button>
                        </div>
                    </div>
                </template>
            </Card>

            <Card
                v-if="activeConfigStep === 'documentos'"
                class="overflow-hidden rounded-xl shadow-md"
            >
                <template #content>
                    <div class="flex flex-col gap-5 p-2 md:p-3">
                        <div
                            class="flex flex-col gap-2 border-b pb-4 md:flex-row md:items-start md:justify-between"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <FileText :size="20" />
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold">
                                        Documentos exigidos
                                    </h3>
                                    <p class="text-sm text-muted-foreground">
                                        Documentos que o candidato deve enviar
                                        para se inscrever (RG, CPF, foto,
                                        comprovantes, etc.).
                                    </p>
                                </div>
                            </div>
                            <Tag
                                :value="`${requiredDocumentsCount} documentos`"
                                severity="info"
                                class="self-start"
                            />
                        </div>

                        <Fieldset legend="Adicionar documento exigido">
                            <Fluid>
                                <form
                                    class="flex flex-col gap-4"
                                    @submit.prevent="
                                        storeRequiredDocumentAction
                                    "
                                >
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">
                                            Tipo de Documento
                                            <span class="text-red-600">*</span>
                                        </span>
                                        <Select
                                            v-model="
                                                requiredDocumentForm.tipo_documento_id
                                            "
                                            :options="props.tiposDocumento"
                                            option-label="descricao"
                                            option-value="id"
                                            placeholder="Selecione o documento exigido"
                                            filter
                                            :invalid="
                                                Boolean(
                                                    requiredDocumentForm.errors
                                                        .tipo_documento_id,
                                                )
                                            "
                                        />
                                        <small
                                            v-if="
                                                requiredDocumentForm.errors
                                                    .tipo_documento_id
                                            "
                                            class="text-sm text-red-600"
                                            >{{
                                                requiredDocumentForm.errors
                                                    .tipo_documento_id
                                            }}</small
                                        >
                                        <small
                                            v-else
                                            class="text-xs text-muted-foreground"
                                        >
                                            Ex.: RG, CPF, Foto 3x4,
                                            Comprovante de residência.
                                        </small>
                                    </label>

                                    <div
                                        class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_220px]"
                                    >
                                        <label class="flex flex-col gap-2">
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
                                            >
                                                Separados por vírgula. Ex.:
                                                pdf, jpg, png.
                                            </small>
                                        </label>
                                        <label class="flex flex-col gap-2">
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

                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm"
                                            >Observação para o candidato</span
                                        >
                                        <Textarea
                                            v-model="
                                                requiredDocumentForm.descricao
                                            "
                                            rows="2"
                                            placeholder="Instruções específicas para envio (opcional)"
                                        />
                                    </label>

                                    <div
                                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-muted/30 px-4 py-3"
                                    >
                                        <div class="flex items-center gap-3">
                                            <ToggleSwitch
                                                v-model="
                                                    requiredDocumentForm.obrigatorio
                                                "
                                            />
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-medium"
                                                >
                                                    {{
                                                        requiredDocumentForm.obrigatorio
                                                            ? 'Documento obrigatório'
                                                            : 'Documento opcional'
                                                    }}
                                                </span>
                                                <small
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Define se o candidato é
                                                    obrigado a enviar este
                                                    documento.
                                                </small>
                                            </div>
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
                                            class="w-auto px-4"
                                        />
                                    </div>
                                </form>
                            </Fluid>
                        </Fieldset>

                        <DataTable
                            :value="
                                props.selectionProcess.required_documents ?? []
                            "
                            striped-rows
                            class="w-full"
                            table-style="width: 100%"
                        >
                            <template #empty>
                                <div
                                    class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center"
                                >
                                    <i
                                        class="pi pi-file text-3xl text-muted-foreground"
                                    />
                                    <p class="text-base font-medium">
                                        Nenhum documento cadastrado
                                    </p>
                                    <p
                                        class="max-w-md text-sm text-muted-foreground"
                                    >
                                        Adicione os documentos exigidos para a
                                        inscrição utilizando o formulário
                                        acima.
                                    </p>
                                </div>
                            </template>

                            <Column
                                header="Documento"
                                header-class="px-4 py-3 min-w-0"
                                body-class="px-4 py-3 min-w-0"
                            >
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <FileText
                                            :size="16"
                                            class="text-primary"
                                        />
                                        <span class="font-medium">
                                            {{
                                                data.tipo_documento
                                                    ?.descricao ??
                                                'Não informado'
                                            }}
                                        </span>
                                    </div>
                                </template>
                            </Column>

                            <Column
                                header="Formatos"
                                header-class="px-4 py-3 whitespace-nowrap"
                                body-class="px-4 py-3 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <div class="flex flex-wrap gap-1">
                                        <Chip
                                            v-for="formato in formatosToList(
                                                data.formatos_aceitos,
                                            )"
                                            :key="formato"
                                            :label="formato.toUpperCase()"
                                            class="text-xs"
                                        />
                                        <span
                                            v-if="
                                                !formatosToList(
                                                    data.formatos_aceitos,
                                                ).length
                                            "
                                            class="text-xs text-muted-foreground"
                                            >-</span
                                        >
                                    </div>
                                </template>
                            </Column>

                            <Column
                                header="Tamanho"
                                header-class="px-4 py-3 w-28 whitespace-nowrap text-center"
                                body-class="px-4 py-3 w-28 whitespace-nowrap text-center"
                            >
                                <template #body="{ data }">
                                    <span class="text-sm font-medium">
                                        {{ data.tamanho_max_mb }} MB
                                    </span>
                                </template>
                            </Column>

                            <Column
                                header="Obrigatório"
                                header-class="px-4 py-3 w-32 whitespace-nowrap text-center"
                                body-class="px-4 py-3 w-32 whitespace-nowrap text-center"
                            >
                                <template #body="{ data }">
                                    <Tag
                                        :value="
                                            data.obrigatorio
                                                ? 'Sim'
                                                : 'Opcional'
                                        "
                                        :severity="
                                            data.obrigatorio
                                                ? 'success'
                                                : 'secondary'
                                        "
                                    />
                                </template>
                            </Column>

                            <Column
                                header="Ações"
                                header-class="px-4 py-3 text-end w-24 whitespace-nowrap"
                                body-class="px-4 py-3 text-end w-24 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <Button
                                        v-tooltip.left="'Remover documento'"
                                        rounded
                                        text
                                        severity="danger"
                                        icon="pi pi-trash"
                                        aria-label="Remover documento"
                                        @click="
                                            confirmRemoveRequiredDocument(data)
                                        "
                                    />
                                </template>
                            </Column>

                            <template #footer>
                                <div
                                    class="px-2 py-3 text-sm text-muted-foreground"
                                >
                                    Total de documentos:
                                    {{ requiredDocumentsCount }}
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <Card
                v-if="activeConfigStep === 'titulos'"
                class="overflow-hidden rounded-xl shadow-md"
            >
                <template #content>
                    <div class="flex flex-col gap-5 p-2 md:p-3">
                        <div
                            class="flex flex-col gap-2 border-b pb-4 md:flex-row md:items-start md:justify-between"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600"
                                >
                                    <GraduationCap :size="20" />
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold">
                                        Títulos aceitos
                                    </h3>
                                    <p class="text-sm text-muted-foreground">
                                        Credenciais que o candidato pode
                                        enviar para receber pontuação na
                                        avaliação (graduação, pós, cursos,
                                        publicações).
                                    </p>
                                </div>
                            </div>
                            <Tag
                                :value="`${requiredTitulosCount} títulos`"
                                severity="warn"
                                class="self-start"
                            />
                        </div>

                        <Fieldset legend="Adicionar título aceito">
                            <Fluid>
                                <form
                                    class="flex flex-col gap-4"
                                    @submit.prevent="storeRequiredTituloAction"
                                >
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">
                                            Tipo de Título
                                            <span class="text-red-600">*</span>
                                        </span>
                                        <Select
                                            v-model="
                                                requiredTituloForm.tipo_titulo_id
                                            "
                                            :options="props.tiposTitulo"
                                            option-label="descricao"
                                            option-value="id"
                                            placeholder="Selecione o título aceito"
                                            filter
                                            :invalid="
                                                Boolean(
                                                    requiredTituloForm.errors
                                                        .tipo_titulo_id,
                                                )
                                            "
                                        >
                                            <template #option="{ option }">
                                                <div
                                                    class="flex items-center justify-between gap-3"
                                                >
                                                    <span>{{
                                                        option.descricao
                                                    }}</span>
                                                    <Tag
                                                        v-if="option.calculo"
                                                        :value="
                                                            calculoLabel(
                                                                option.calculo,
                                                            )
                                                        "
                                                        severity="secondary"
                                                        class="text-xs"
                                                    />
                                                </div>
                                            </template>
                                        </Select>
                                        <small
                                            v-if="
                                                requiredTituloForm.errors
                                                    .tipo_titulo_id
                                            "
                                            class="text-sm text-red-600"
                                            >{{
                                                requiredTituloForm.errors
                                                    .tipo_titulo_id
                                            }}</small
                                        >
                                        <small
                                            v-else
                                            class="text-xs text-muted-foreground"
                                        >
                                            Ex.: Graduação, Mestrado,
                                            Especialização, Curso de Inglês.
                                        </small>
                                    </label>

                                    <div
                                        class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                    >
                                        <label class="flex flex-col gap-2">
                                            <span class="text-sm">
                                                Pontuação máxima
                                                <span class="text-red-600"
                                                    >*</span
                                                >
                                            </span>
                                            <InputNumber
                                                v-model="
                                                    requiredTituloForm.pontuacao_max
                                                "
                                                :min="0"
                                                :max="9999.99"
                                                :min-fraction-digits="2"
                                                :max-fraction-digits="2"
                                                :invalid="
                                                    Boolean(
                                                        requiredTituloForm
                                                            .errors
                                                            .pontuacao_max,
                                                    )
                                                "
                                                fluid
                                            />
                                            <small
                                                class="text-xs text-muted-foreground"
                                            >
                                                Pontos máximos que este
                                                título pode somar.
                                            </small>
                                        </label>
                                        <label class="flex flex-col gap-2">
                                            <span class="text-sm"
                                                >Quantidade máxima</span
                                            >
                                            <InputNumber
                                                v-model="
                                                    requiredTituloForm.qtd_maxima
                                                "
                                                :min="1"
                                                :max="999"
                                                show-buttons
                                                placeholder="Sem limite"
                                                fluid
                                            />
                                            <small
                                                class="text-xs text-muted-foreground"
                                            >
                                                Quantos itens deste tipo o
                                                candidato pode enviar (em
                                                branco = sem limite).
                                            </small>
                                        </label>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_220px]"
                                    >
                                        <label class="flex flex-col gap-2">
                                            <span class="text-sm"
                                                >Formatos aceitos</span
                                            >
                                            <InputText
                                                v-model="
                                                    requiredTituloForm.formatos_aceitos
                                                "
                                                placeholder="pdf, jpg, png"
                                            />
                                            <small
                                                class="text-xs text-muted-foreground"
                                            >
                                                Separados por vírgula.
                                            </small>
                                        </label>
                                        <label class="flex flex-col gap-2">
                                            <span class="text-sm"
                                                >Tamanho máximo</span
                                            >
                                            <InputNumber
                                                v-model="
                                                    requiredTituloForm.tamanho_max_mb
                                                "
                                                :min="1"
                                                :max="100"
                                                suffix=" MB"
                                                show-buttons
                                                fluid
                                            />
                                        </label>
                                    </div>

                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm"
                                            >Observação para o candidato</span
                                        >
                                        <Textarea
                                            v-model="
                                                requiredTituloForm.descricao
                                            "
                                            rows="2"
                                            placeholder="Instruções específicas para envio (opcional)"
                                        />
                                    </label>

                                    <div
                                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-muted/30 px-4 py-3"
                                    >
                                        <div class="flex items-center gap-3">
                                            <ToggleSwitch
                                                v-model="
                                                    requiredTituloForm.obrigatorio
                                                "
                                            />
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-medium"
                                                >
                                                    {{
                                                        requiredTituloForm.obrigatorio
                                                            ? 'Pelo menos um exigido'
                                                            : 'Envio opcional'
                                                    }}
                                                </span>
                                                <small
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Define se o candidato é
                                                    obrigado a enviar pelo
                                                    menos um título deste tipo.
                                                </small>
                                            </div>
                                        </div>
                                        <Button
                                            :fluid="false"
                                            type="submit"
                                            size="small"
                                            icon="pi pi-plus"
                                            label="Adicionar título"
                                            :loading="
                                                requiredTituloForm.processing
                                            "
                                            :disabled="
                                                !requiredTituloForm.tipo_titulo_id ||
                                                requiredTituloForm.processing
                                            "
                                            class="w-auto px-4"
                                        />
                                    </div>
                                </form>
                            </Fluid>
                        </Fieldset>

                        <DataTable
                            :value="
                                props.selectionProcess.required_titulos ?? []
                            "
                            striped-rows
                            class="w-full"
                            table-style="width: 100%"
                        >
                            <template #empty>
                                <div
                                    class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center"
                                >
                                    <i
                                        class="pi pi-graduation-cap text-3xl text-muted-foreground"
                                    />
                                    <p class="text-base font-medium">
                                        Nenhum título cadastrado
                                    </p>
                                    <p
                                        class="max-w-md text-sm text-muted-foreground"
                                    >
                                        Adicione os títulos aceitos para
                                        avaliação utilizando o formulário
                                        acima.
                                    </p>
                                </div>
                            </template>

                            <Column
                                header="Título"
                                header-class="px-4 py-3 min-w-0"
                                body-class="px-4 py-3 min-w-0"
                            >
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <GraduationCap
                                            :size="16"
                                            class="text-amber-600"
                                        />
                                        <span class="font-medium">
                                            {{
                                                data.tipo_titulo?.descricao ??
                                                'Não informado'
                                            }}
                                        </span>
                                    </div>
                                </template>
                            </Column>

                            <Column
                                header="Cálculo"
                                header-class="px-4 py-3 w-32 whitespace-nowrap"
                                body-class="px-4 py-3 w-32 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <Tag
                                        v-if="data.tipo_titulo?.calculo"
                                        :value="
                                            calculoLabel(
                                                data.tipo_titulo.calculo,
                                            )
                                        "
                                        severity="secondary"
                                        class="text-xs"
                                    />
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                        >-</span
                                    >
                                </template>
                            </Column>

                            <Column
                                header="Pontuação Máx."
                                header-class="px-4 py-3 w-32 whitespace-nowrap text-center"
                                body-class="px-4 py-3 w-32 whitespace-nowrap text-center"
                            >
                                <template #body="{ data }">
                                    <span class="text-sm font-semibold">
                                        {{ formatPontuacao(data.pontuacao_max) }}
                                    </span>
                                </template>
                            </Column>

                            <Column
                                header="Qtd. Máx."
                                header-class="px-4 py-3 w-28 whitespace-nowrap text-center"
                                body-class="px-4 py-3 w-28 whitespace-nowrap text-center"
                            >
                                <template #body="{ data }">
                                    <span
                                        v-if="data.qtd_maxima"
                                        class="text-sm font-medium"
                                    >
                                        {{ data.qtd_maxima }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                    >
                                        Sem limite
                                    </span>
                                </template>
                            </Column>

                            <Column
                                header="Formatos"
                                header-class="px-4 py-3 whitespace-nowrap"
                                body-class="px-4 py-3 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <div class="flex flex-wrap gap-1">
                                        <Chip
                                            v-for="formato in formatosToList(
                                                data.formatos_aceitos,
                                            )"
                                            :key="formato"
                                            :label="formato.toUpperCase()"
                                            class="text-xs"
                                        />
                                        <span
                                            v-if="
                                                !formatosToList(
                                                    data.formatos_aceitos,
                                                ).length
                                            "
                                            class="text-xs text-muted-foreground"
                                            >-</span
                                        >
                                    </div>
                                </template>
                            </Column>

                            <Column
                                header="Obrigatório"
                                header-class="px-4 py-3 w-32 whitespace-nowrap text-center"
                                body-class="px-4 py-3 w-32 whitespace-nowrap text-center"
                            >
                                <template #body="{ data }">
                                    <Tag
                                        :value="
                                            data.obrigatorio ? 'Sim' : 'Não'
                                        "
                                        :severity="
                                            data.obrigatorio
                                                ? 'success'
                                                : 'secondary'
                                        "
                                    />
                                </template>
                            </Column>

                            <Column
                                header="Ações"
                                header-class="px-4 py-3 text-end w-24 whitespace-nowrap"
                                body-class="px-4 py-3 text-end w-24 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <Button
                                        v-tooltip.left="'Remover título'"
                                        rounded
                                        text
                                        severity="danger"
                                        icon="pi pi-trash"
                                        aria-label="Remover título"
                                        @click="
                                            confirmRemoveRequiredTitulo(data)
                                        "
                                    />
                                </template>
                            </Column>

                            <template #footer>
                                <div
                                    class="px-2 py-3 text-sm text-muted-foreground"
                                >
                                    Total de títulos:
                                    {{ requiredTitulosCount }}
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="rounded-xl shadow-md">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <Layers :size="18" class="text-amber-600" />
                            <span>Etapas</span>
                        </div>
                    </template>
                    <template #content>
                        <ul class="flex flex-col gap-2 text-sm">
                            <li
                                v-for="stage in props.selectionProcess.stages ??
                                []"
                                :key="stage.id"
                                class="flex items-center gap-3 rounded-lg border p-3"
                            >
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-500/10 text-xs font-semibold text-amber-700"
                                >
                                    {{ stage.ordem }}
                                </span>
                                <span>{{ stage.nome }}</span>
                            </li>
                            <li
                                v-if="!stagesCount"
                                class="rounded-lg border border-dashed p-4 text-center text-muted-foreground"
                            >
                                Nenhuma etapa cadastrada.
                            </li>
                        </ul>
                    </template>
                </Card>

                <Card class="rounded-xl shadow-md">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <FileBadge :size="18" class="text-emerald-600" />
                            <span>Critérios</span>
                        </div>
                    </template>
                    <template #content>
                        <Fluid>
                            <form
                                class="mb-4 flex flex-col gap-3"
                                @submit.prevent="storeCriteriaAction"
                            >
                                <InputText
                                    v-model="criteriaForm.nome"
                                    placeholder="Nome do critério"
                                />
                                <div
                                    class="grid grid-cols-1 gap-3 md:grid-cols-3"
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
                                    <InputNumber
                                        v-model="criteriaForm.pontuacao_max"
                                        :min="1"
                                        :max="1000"
                                        :min-fraction-digits="0"
                                        :max-fraction-digits="2"
                                        placeholder="Pontuação máxima"
                                        fluid
                                    />
                                    <InputNumber
                                        v-model="criteriaForm.ordem"
                                        :min="1"
                                        :max="999"
                                        show-buttons
                                        placeholder="Ordem"
                                        fluid
                                    />
                                </div>
                                <div class="flex justify-end gap-2">
                                    <Button
                                        :fluid="false"
                                        type="submit"
                                        size="small"
                                        icon="pi pi-check"
                                        label="Salvar critério"
                                        :loading="criteriaForm.processing"
                                    />
                                </div>
                            </form>
                        </Fluid>

                        <Divider />

                        <ul class="flex flex-col gap-2 text-sm">
                            <li
                                v-for="criteria in props.selectionProcess
                                    .criteria ?? []"
                                :key="criteria.id"
                                class="flex items-center justify-between gap-3 rounded-lg border p-3"
                            >
                                <div class="flex flex-col">
                                    <span class="font-medium">
                                        {{ criteria.nome }}
                                    </span>
                                    <span
                                        class="text-xs text-muted-foreground"
                                    >
                                        Peso: {{ criteria.peso }} | Máx.:
                                        {{ criteria.pontuacao_max }} | Ordem:
                                        {{ criteria.ordem }}
                                    </span>
                                </div>
                                <Button
                                    v-tooltip.left="'Remover critério'"
                                    rounded
                                    text
                                    severity="danger"
                                    icon="pi pi-trash"
                                    aria-label="Remover critério"
                                    @click="deleteCriteriaAction(criteria.id)"
                                />
                            </li>
                            <li
                                v-if="!criteriaCount"
                                class="rounded-lg border border-dashed p-4 text-center text-muted-foreground"
                            >
                                Nenhum critério cadastrado.
                            </li>
                        </ul>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>
