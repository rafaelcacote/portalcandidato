<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import Fluid from 'primevue/fluid';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/admin/processes';
import { destroy as destroyApplicationField, store as storeApplicationField } from '@/routes/admin/processes/application-fields';
import { destroy as destroyCriteria, store as storeCriteria } from '@/routes/admin/processes/criteria';
import { destroy as destroyRequiredDocument, store as storeRequiredDocument } from '@/routes/admin/processes/required-documents';

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
        tipo_titulo_id?: number | null;
        nome: string;
        descricao?: string | null;
        formatos_aceitos?: string[] | null;
        tamanho_max_mb: number;
        obrigatorio: boolean;
        tipo_documento?: { id: number; descricao: string } | null;
        tipo_titulo?: { id: number; descricao: string; calculo?: string | null } | null;
    }>;
    criteria?: Array<{
        id: number;
        nome: string;
        peso: number;
        ordem: number;
        pontuacao_max: number;
    }>;
    application_fields?: Array<{
        id: number;
        label: string;
        field_key: string;
        tipo: string;
        obrigatorio: boolean;
        opcoes?: string[] | null;
        ordem: number;
    }>;
    evaluator_assignments?: Array<{ id: number }>;
};

const props = defineProps<{
    selectionProcess: SelectionProcess;
    tiposDocumento: Array<{ id: number; descricao: string }>;
    tiposTitulo: Array<{ id: number; descricao: string; calculo?: string | null }>;
}>();

const fieldTypeOptions = [
    { label: 'Texto curto', value: 'text' },
    { label: 'Texto longo', value: 'textarea' },
    { label: 'Numérico', value: 'number' },
    { label: 'Data', value: 'date' },
    { label: 'Seleção', value: 'select' },
];

const requiredDocumentForm = useForm({
    tipo_documento_id: null as number | null,
    tipo_titulo_id: null as number | null,
    descricao: '',
    formatos_aceitos: 'pdf,jpg,png',
    tamanho_max_mb: 10,
    obrigatorio: true,
});

const criteriaForm = useForm({
    nome: '',
    peso: 1,
    pontuacao_max: 10,
    ordem: 1,
});

const applicationFieldForm = useForm({
    label: '',
    field_key: '',
    tipo: 'text',
    obrigatorio: false,
    opcoes: '',
    ordem: 1,
});

const storeRequiredDocumentAction = (): void => {
    requiredDocumentForm.post(storeRequiredDocument(props.selectionProcess.id).url, {
        preserveScroll: true,
        onSuccess: () => requiredDocumentForm.reset(),
    });
};

const storeCriteriaAction = (): void => {
    criteriaForm.post(storeCriteria(props.selectionProcess.id).url, {
        preserveScroll: true,
        onSuccess: () => criteriaForm.reset(),
    });
};

const storeApplicationFieldAction = (): void => {
    applicationFieldForm.post(storeApplicationField(props.selectionProcess.id).url, {
        preserveScroll: true,
        onSuccess: () => applicationFieldForm.reset(),
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

const deleteCriteriaAction = (criteriaId: number): void => {
    router.delete(
        destroyCriteria({
            selectionProcess: props.selectionProcess.id,
            processCriteria: criteriaId,
        }).url,
        { preserveScroll: true },
    );
};

const deleteApplicationFieldAction = (fieldId: number): void => {
    router.delete(
        destroyApplicationField({
            selectionProcess: props.selectionProcess.id,
            processApplicationField: fieldId,
        }).url,
        { preserveScroll: true },
    );
};
</script>

<template>
    <div class="p-1">
        <div class="mx-auto flex w-full max-w-[1520px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    :title="`Configuração: ${props.selectionProcess.titulo}`"
                    description="Gerencie dados gerais, etapas, documentos, critérios e avaliadores."
                    :icon="FileText"
                />
                <Link :href="edit(props.selectionProcess.id).url">
                    <Button label="Editar processo" icon="pi pi-pencil" />
                </Link>
            </div>

            <Card class="rounded-xl shadow-md">
                <template #content>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border p-4">
                            <p class="text-xs text-muted-foreground">Status</p>
                            <p class="mt-1 text-sm font-semibold">
                                {{ props.selectionProcess.status }}
                            </p>
                        </div>
                        <div class="rounded-xl border p-4">
                            <p class="text-xs text-muted-foreground">
                                Período de inscrição
                            </p>
                            <p class="mt-1 text-sm font-semibold">
                                {{
                                    props.selectionProcess.inscricao_inicio_em ??
                                    '-'
                                }}
                            </p>
                            <p class="text-sm font-semibold">
                                {{
                                    props.selectionProcess.inscricao_fim_em ??
                                    '-'
                                }}
                            </p>
                        </div>
                        <div class="rounded-xl border p-4">
                            <p class="text-xs text-muted-foreground">Etapas</p>
                            <p class="mt-1 text-2xl font-semibold">
                                {{ props.selectionProcess.stages?.length ?? 0 }}
                            </p>
                        </div>
                        <div class="rounded-xl border p-4">
                            <p class="text-xs text-muted-foreground">
                                Avaliadores vinculados
                            </p>
                            <p class="mt-1 text-2xl font-semibold">
                                {{
                                    props.selectionProcess.evaluator_assignments
                                        ?.length ?? 0
                                }}
                            </p>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid gap-4 xl:grid-cols-3">
                <Card class="rounded-xl shadow-md">
                    <template #title>Etapas</template>
                    <template #content>
                        <ul class="flex flex-col gap-2 text-sm">
                            <li
                                v-for="stage in props.selectionProcess.stages ?? []"
                                :key="stage.id"
                                class="rounded-lg border p-3"
                            >
                                {{ stage.ordem }} - {{ stage.nome }}
                            </li>
                            <li
                                v-if="!(props.selectionProcess.stages ?? []).length"
                                class="text-muted-foreground"
                            >
                                Nenhuma etapa cadastrada.
                            </li>
                        </ul>
                    </template>
                </Card>
                <Card class="rounded-xl shadow-md">
                    <template #title>Campos dinâmicos da inscrição</template>
                    <template #content>
                        <Fluid>
                            <form class="mb-4 flex flex-col gap-3" @submit.prevent="storeApplicationFieldAction">
                                <InputText
                                    v-model="applicationFieldForm.label"
                                    placeholder="Rótulo (ex: CPF)"
                                />
                                <InputText
                                    v-model="applicationFieldForm.field_key"
                                    placeholder="Chave técnica (ex: cpf)"
                                />
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <Select
                                        v-model="applicationFieldForm.tipo"
                                        :options="fieldTypeOptions"
                                        option-label="label"
                                        option-value="value"
                                    />
                                    <InputNumber
                                        v-model="applicationFieldForm.ordem"
                                        :min="1"
                                        show-buttons
                                        fluid
                                    />
                                    <div class="flex items-center gap-2 rounded border px-3">
                                        <Checkbox
                                            v-model="applicationFieldForm.obrigatorio"
                                            binary
                                            input-id="field-obrigatorio"
                                        />
                                        <label for="field-obrigatorio" class="text-sm"
                                            >Obrigatório</label
                                        >
                                    </div>
                                </div>
                                <InputText
                                    v-model="applicationFieldForm.opcoes"
                                    placeholder="Opções (se select), separadas por vírgula"
                                />
                                <div class="flex justify-end">
                                    <Button type="submit" icon="pi pi-plus" label="Adicionar campo" />
                                </div>
                            </form>
                        </Fluid>

                        <ul class="flex flex-col gap-2 text-sm">
                            <li
                                v-for="field in props.selectionProcess.application_fields ?? []"
                                :key="field.id"
                                class="flex items-center justify-between gap-3 rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">{{ field.label }} ({{ field.field_key }})</p>
                                    <p class="text-muted-foreground">
                                        Tipo: {{ field.tipo }} | Ordem: {{ field.ordem }} |
                                        {{ field.obrigatorio ? 'Obrigatório' : 'Opcional' }}
                                    </p>
                                </div>
                                <Button
                                    rounded
                                    text
                                    severity="danger"
                                    icon="pi pi-trash"
                                    aria-label="Remover campo"
                                    @click="deleteApplicationFieldAction(field.id)"
                                />
                            </li>
                            <li
                                v-if="!(props.selectionProcess.application_fields ?? []).length"
                                class="text-muted-foreground"
                            >
                                Nenhum campo dinâmico cadastrado.
                            </li>
                        </ul>
                    </template>
                </Card>

                <Card class="rounded-xl shadow-md">
                    <template #title>Documentos obrigatórios</template>
                    <template #content>
                        <Fluid>
                            <form class="mb-4 flex flex-col gap-3" @submit.prevent="storeRequiredDocumentAction">
                                <Textarea
                                    v-model="requiredDocumentForm.descricao"
                                    rows="2"
                                    placeholder="Descrição (opcional)"
                                />
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <Select
                                        v-model="requiredDocumentForm.tipo_documento_id"
                                        :options="props.tiposDocumento"
                                        option-label="descricao"
                                        option-value="id"
                                        placeholder="Tipo de documento"
                                    />
                                    <Select
                                        v-model="requiredDocumentForm.tipo_titulo_id"
                                        :options="props.tiposTitulo"
                                        option-label="descricao"
                                        option-value="id"
                                        placeholder="Tipo de título"
                                    />
                                </div>
                                <Textarea
                                    v-model="requiredDocumentForm.formatos_aceitos"
                                    rows="2"
                                    placeholder="Formatos aceitos (pdf,jpg,png)"
                                />
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <InputNumber
                                        v-model="requiredDocumentForm.tamanho_max_mb"
                                        :min="1"
                                        :max="100"
                                        suffix=" MB"
                                        show-buttons
                                        fluid
                                    />
                                </div>
                                <div class="flex items-center gap-2 rounded border px-3 py-2">
                                    <Checkbox
                                        v-model="requiredDocumentForm.obrigatorio"
                                        binary
                                        input-id="doc-obrigatorio"
                                    />
                                    <label for="doc-obrigatorio" class="text-sm"
                                        >Documento obrigatório</label
                                    >
                                </div>
                                <div class="flex justify-end">
                                    <Button
                                        type="submit"
                                        icon="pi pi-plus"
                                        label="Adicionar documento"
                                    />
                                </div>
                            </form>
                        </Fluid>

                        <ul class="flex flex-col gap-2 text-sm">
                            <li
                                v-for="doc in props.selectionProcess.required_documents ?? []"
                                :key="doc.id"
                                class="flex items-center justify-between gap-3 rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{ doc.tipo_documento?.descricao ?? 'Documento' }}
                                        -
                                        {{ doc.tipo_titulo?.descricao ?? 'Título' }}
                                    </p>
                                    <p class="text-muted-foreground">
                                        {{ doc.tamanho_max_mb }} MB |
                                        {{ doc.obrigatorio ? 'Obrigatório' : 'Opcional' }}
                                    </p>
                                    <p
                                        v-if="doc.tipo_titulo?.calculo"
                                        class="text-muted-foreground"
                                    >
                                        Cálculo: {{ doc.tipo_titulo.calculo }}
                                    </p>
                                </div>
                                <Button
                                    rounded
                                    text
                                    severity="danger"
                                    icon="pi pi-trash"
                                    aria-label="Remover documento"
                                    @click="deleteRequiredDocumentAction(doc.id)"
                                />
                            </li>
                            <li
                                v-if="
                                    !(props.selectionProcess.required_documents ?? [])
                                        .length
                                "
                                class="text-muted-foreground"
                            >
                                Nenhum documento cadastrado.
                            </li>
                        </ul>
                    </template>
                </Card>

                <Card class="rounded-xl shadow-md">
                    <template #title>Critérios</template>
                    <template #content>
                        <Fluid>
                            <form class="mb-4 flex flex-col gap-3" @submit.prevent="storeCriteriaAction">
                                <InputText
                                    v-model="criteriaForm.nome"
                                    placeholder="Nome do critério"
                                />
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
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
                                <div class="flex justify-end">
                                    <Button
                                        type="submit"
                                        icon="pi pi-plus"
                                        label="Adicionar critério"
                                    />
                                </div>
                            </form>
                        </Fluid>

                        <ul class="flex flex-col gap-2 text-sm">
                            <li
                                v-for="criteria in props.selectionProcess.criteria ?? []"
                                :key="criteria.id"
                                class="flex items-center justify-between gap-3 rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">{{ criteria.nome }}</p>
                                    <p class="text-muted-foreground">
                                        Peso: {{ criteria.peso }} | Max:
                                        {{ criteria.pontuacao_max }} | Ordem:
                                        {{ criteria.ordem }}
                                    </p>
                                </div>
                                <Button
                                    rounded
                                    text
                                    severity="danger"
                                    icon="pi pi-trash"
                                    aria-label="Remover critério"
                                    @click="deleteCriteriaAction(criteria.id)"
                                />
                            </li>
                            <li
                                v-if="!(props.selectionProcess.criteria ?? []).length"
                                class="text-muted-foreground"
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
