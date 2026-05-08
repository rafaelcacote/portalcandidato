<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { Users } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Fieldset from 'primevue/fieldset';
import Fluid from 'primevue/fluid';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import {
    index,
    store as storeEvaluator,
    update as updateEvaluator,
} from '@/routes/admin/evaluators';
import {
    destroy as destroyAssignment,
    store as storeAssignment,
    update as updateAssignment,
} from '@/routes/admin/evaluators/assignments';

type AssignmentPermissions = {
    pode_avaliar: boolean;
    pode_visualizar_resultados: boolean;
    pode_baixar_documentos: boolean;
};

type Assignment = AssignmentPermissions & {
    id: number;
    selection_process_id: number;
    selection_process: {
        id: number;
        titulo: string;
        status: string;
    };
};

type EvaluatorEdit = {
    id: number;
    name: string;
    email: string;
    cpf?: string | null;
    telefone?: string | null;
    ativo: boolean;
    assignments: Assignment[];
};

type ProcessOption = {
    id: number;
    titulo: string;
    status: string;
};

const props = defineProps<{
    evaluator?: EvaluatorEdit;
    processes?: ProcessOption[];
}>();

const vTooltip = Tooltip;
const confirm = useConfirm();

const isEditing = (): boolean => Boolean(props.evaluator?.id);

const form = useForm({
    name: props.evaluator?.name ?? '',
    email: props.evaluator?.email ?? '',
    cpf: props.evaluator?.cpf ?? '',
    telefone: props.evaluator?.telefone ?? '',
    password: '',
    ativo: props.evaluator?.ativo ?? true,
});

const assignmentForm = useForm({
    selection_process_id: null as number | null,
    pode_avaliar: true,
    pode_visualizar_resultados: false,
    pode_baixar_documentos: true,
});

const clientErrors = ref<Record<string, string>>({});

function errorMessage(field: string): string {
    const client = clientErrors.value[field];

    if (client) {
        return client;
    }

    const server = form.errors[field];

    if (typeof server === 'string') {
        return server;
    }

    if (Array.isArray(server) && server.length > 0) {
        return server[0];
    }

    return '';
}

function fieldInvalid(field: string): boolean {
    return errorMessage(field).length > 0;
}

function touchField(field: string): void {
    form.clearErrors(field);
    if (!clientErrors.value[field]) {
        return;
    }
    const next = { ...clientErrors.value };
    delete next[field];
    clientErrors.value = next;
}

function validateClient(): boolean {
    clientErrors.value = {};
    let valid = true;

    if (!form.name.trim()) {
        clientErrors.value.name = 'Este campo é obrigatório.';
        valid = false;
    }

    if (!form.email.trim()) {
        clientErrors.value.email = 'Este campo é obrigatório.';
        valid = false;
    } else if (!/^.+@.+\..+$/.test(form.email)) {
        clientErrors.value.email = 'Informe um e-mail válido.';
        valid = false;
    }

    if (!isEditing() && form.password.length < 8) {
        clientErrors.value.password =
            'A senha deve ter no mínimo 8 caracteres.';
        valid = false;
    } else if (isEditing() && form.password && form.password.length < 8) {
        clientErrors.value.password =
            'A senha deve ter no mínimo 8 caracteres.';
        valid = false;
    }

    return valid;
}

const submit = (): void => {
    if (!validateClient()) {
        return;
    }

    if (props.evaluator?.id) {
        form.put(updateEvaluator(props.evaluator.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                form.password = '';
            },
        });

        return;
    }

    form.post(storeEvaluator().url);
};

const submitAssignment = (): void => {
    if (!props.evaluator?.id) {
        return;
    }

    if (!assignmentForm.selection_process_id) {
        return;
    }

    assignmentForm.post(storeAssignment(props.evaluator.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            assignmentForm.reset();
            assignmentForm.pode_avaliar = true;
            assignmentForm.pode_visualizar_resultados = false;
            assignmentForm.pode_baixar_documentos = true;
        },
    });
};

const togglePermission = (
    assignment: Assignment,
    field: keyof AssignmentPermissions,
): void => {
    if (!props.evaluator?.id) {
        return;
    }

    router.put(
        updateAssignment([props.evaluator.id, assignment.id]).url,
        {
            pode_avaliar: assignment.pode_avaliar,
            pode_visualizar_resultados: assignment.pode_visualizar_resultados,
            pode_baixar_documentos: assignment.pode_baixar_documentos,
            [field]: assignment[field],
        },
        { preserveScroll: true },
    );
};

const removeAssignment = (assignment: Assignment): void => {
    if (!props.evaluator?.id) {
        return;
    }

    router.delete(destroyAssignment([props.evaluator.id, assignment.id]).url, {
        preserveScroll: true,
    });
};

const confirmRemoveAssignment = (assignment: Assignment): void => {
    confirm.require({
        header: 'Confirmar exclusão',
        message: `Remover a atribuição ao processo "${assignment.selection_process.titulo}"?`,
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Remover',
        rejectProps: {
            outlined: true,
            icon: 'pi pi-times',
        },
        acceptProps: {
            severity: 'danger',
            icon: 'pi pi-trash',
        },
        accept: () => {
            removeAssignment(assignment);
        },
    });
};
</script>

<template>
    <div class="p-1">
        <ConfirmDialog />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    :title="
                        isEditing() ? 'Editar Avaliador' : 'Novo Avaliador'
                    "
                    description="Cadastre os dados do avaliador e gerencie suas atribuições aos processos."
                    :icon="Users"
                />
                <Link :href="index().url">
                    <Button
                        label="Voltar"
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        outlined
                        size="small"
                    />
                </Link>
            </div>

            <Card class="rounded-xl shadow-md">
                <template #content>
                    <Fluid>
                        <form
                            class="flex flex-col gap-5 p-2 md:p-3"
                            @submit.prevent="submit"
                        >
                            <Fieldset legend="Dados do avaliador">
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">
                                            Nome
                                            <span class="text-red-600">*</span>
                                        </span>
                                        <InputText
                                            v-model="form.name"
                                            placeholder="Nome completo"
                                            :invalid="fieldInvalid('name')"
                                            maxlength="255"
                                            @update:model-value="
                                                touchField('name')
                                            "
                                        />
                                        <small
                                            v-if="errorMessage('name')"
                                            class="text-sm text-red-600"
                                        >
                                            {{ errorMessage('name') }}
                                        </small>
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">
                                            E-mail
                                            <span class="text-red-600">*</span>
                                        </span>
                                        <InputText
                                            v-model="form.email"
                                            type="email"
                                            placeholder="email@dominio.com"
                                            :invalid="fieldInvalid('email')"
                                            maxlength="255"
                                            @update:model-value="
                                                touchField('email')
                                            "
                                        />
                                        <small
                                            v-if="errorMessage('email')"
                                            class="text-sm text-red-600"
                                        >
                                            {{ errorMessage('email') }}
                                        </small>
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">CPF</span>
                                        <InputText
                                            v-model="form.cpf"
                                            placeholder="000.000.000-00"
                                            :invalid="fieldInvalid('cpf')"
                                            maxlength="14"
                                            @update:model-value="
                                                touchField('cpf')
                                            "
                                        />
                                        <small
                                            v-if="errorMessage('cpf')"
                                            class="text-sm text-red-600"
                                        >
                                            {{ errorMessage('cpf') }}
                                        </small>
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">Telefone</span>
                                        <InputText
                                            v-model="form.telefone"
                                            placeholder="(00) 00000-0000"
                                            :invalid="fieldInvalid('telefone')"
                                            maxlength="20"
                                            @update:model-value="
                                                touchField('telefone')
                                            "
                                        />
                                        <small
                                            v-if="errorMessage('telefone')"
                                            class="text-sm text-red-600"
                                        >
                                            {{ errorMessage('telefone') }}
                                        </small>
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">
                                            {{
                                                isEditing()
                                                    ? 'Nova senha (opcional)'
                                                    : 'Senha'
                                            }}
                                            <span
                                                v-if="!isEditing()"
                                                class="text-red-600"
                                                >*</span
                                            >
                                        </span>
                                        <Password
                                            v-model="form.password"
                                            :feedback="false"
                                            toggle-mask
                                            :invalid="fieldInvalid('password')"
                                            input-class="w-full"
                                            :placeholder="
                                                isEditing()
                                                    ? 'Manter senha atual'
                                                    : 'Mínimo 8 caracteres'
                                            "
                                            @update:model-value="
                                                touchField('password')
                                            "
                                        />
                                        <small
                                            v-if="errorMessage('password')"
                                            class="text-sm text-red-600"
                                        >
                                            {{ errorMessage('password') }}
                                        </small>
                                    </label>
                                    <div
                                        class="flex max-w-xs items-center gap-3 rounded border px-3 py-2"
                                    >
                                        <ToggleSwitch v-model="form.ativo" />
                                        <span class="text-sm">{{
                                            form.ativo ? 'Ativo' : 'Inativo'
                                        }}</span>
                                    </div>
                                </div>
                            </Fieldset>

                            <div
                                class="mt-2 flex w-full items-center justify-end gap-2"
                            >
                                <Link :href="index().url">
                                    <Button
                                        :fluid="false"
                                        size="small"
                                        type="button"
                                        label="Cancelar"
                                        icon="pi pi-times"
                                        severity="secondary"
                                        outlined
                                        class="w-auto px-3"
                                    />
                                </Link>
                                <Button
                                    :fluid="false"
                                    size="small"
                                    type="submit"
                                    label="Salvar avaliador"
                                    icon="pi pi-check"
                                    :loading="form.processing"
                                    class="w-auto px-3"
                                />
                            </div>
                        </form>
                    </Fluid>
                </template>
            </Card>

            <Card v-if="isEditing()" class="rounded-xl shadow-md">
                <template #content>
                    <div class="flex flex-col gap-5 p-2 md:p-3">
                        <div
                            class="flex flex-col gap-1 border-b pb-3"
                        >
                            <h3 class="text-base font-semibold">
                                Atribuições aos processos
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Vincule este avaliador aos processos seletivos
                                e defina as permissões para cada um.
                            </p>
                        </div>

                        <Fieldset legend="Vincular a um novo processo">
                            <Fluid>
                                <form
                                    class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_auto] md:items-end"
                                    @submit.prevent="submitAssignment"
                                >
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">
                                            Processo
                                            <span class="text-red-600">*</span>
                                        </span>
                                        <Select
                                            v-model="
                                                assignmentForm.selection_process_id
                                            "
                                            :options="props.processes ?? []"
                                            option-label="titulo"
                                            option-value="id"
                                            placeholder="Selecione um processo"
                                            :invalid="
                                                Boolean(
                                                    assignmentForm.errors
                                                        .selection_process_id,
                                                )
                                            "
                                        />
                                        <small
                                            v-if="
                                                assignmentForm.errors
                                                    .selection_process_id
                                            "
                                            class="text-sm text-red-600"
                                        >
                                            {{
                                                assignmentForm.errors
                                                    .selection_process_id
                                            }}
                                        </small>
                                    </label>
                                    <Button
                                        :fluid="false"
                                        size="small"
                                        type="submit"
                                        label="Adicionar atribuição"
                                        icon="pi pi-plus"
                                        :loading="assignmentForm.processing"
                                        class="w-auto px-3"
                                    />
                                </form>

                                <div
                                    class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3"
                                >
                                    <label
                                        class="flex items-center gap-2 rounded border px-3 py-2"
                                    >
                                        <Checkbox
                                            v-model="
                                                assignmentForm.pode_avaliar
                                            "
                                            binary
                                        />
                                        <span class="text-sm">
                                            Pode avaliar inscrições
                                        </span>
                                    </label>
                                    <label
                                        class="flex items-center gap-2 rounded border px-3 py-2"
                                    >
                                        <Checkbox
                                            v-model="
                                                assignmentForm.pode_visualizar_resultados
                                            "
                                            binary
                                        />
                                        <span class="text-sm">
                                            Pode visualizar resultados
                                        </span>
                                    </label>
                                    <label
                                        class="flex items-center gap-2 rounded border px-3 py-2"
                                    >
                                        <Checkbox
                                            v-model="
                                                assignmentForm.pode_baixar_documentos
                                            "
                                            binary
                                        />
                                        <span class="text-sm">
                                            Pode baixar documentos
                                        </span>
                                    </label>
                                </div>
                            </Fluid>
                        </Fieldset>

                        <DataTable
                            :value="props.evaluator?.assignments ?? []"
                            striped-rows
                            class="w-full"
                            table-style="width: 100%; table-layout: fixed"
                        >
                            <template #empty>
                                <div
                                    class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center"
                                >
                                    <i
                                        class="pi pi-link text-3xl text-muted-foreground"
                                    />
                                    <p class="text-base font-medium">
                                        Nenhuma atribuição registrada
                                    </p>
                                    <p
                                        class="max-w-md text-sm text-muted-foreground"
                                    >
                                        Vincule este avaliador a um processo
                                        utilizando o formulário acima.
                                    </p>
                                </div>
                            </template>

                            <Column
                                header="Processo"
                                header-class="px-4 py-3 min-w-0"
                                body-class="px-4 py-3 min-w-0"
                            >
                                <template #body="{ data }">
                                    <div class="flex flex-col">
                                        <span class="font-medium">
                                            {{
                                                data.selection_process.titulo
                                            }}
                                        </span>
                                        <small
                                            class="text-xs text-muted-foreground"
                                        >
                                            #{{
                                                data.selection_process.id
                                            }}
                                        </small>
                                    </div>
                                </template>
                            </Column>
                            <Column
                                header="Status do processo"
                                header-class="px-4 py-3 w-36 whitespace-nowrap"
                                body-class="px-4 py-3 w-36 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <Tag
                                        :value="data.selection_process.status"
                                        :severity="
                                            data.selection_process.status ===
                                            'ativo'
                                                ? 'success'
                                                : data.selection_process
                                                        .status === 'encerrado'
                                                  ? 'warn'
                                                  : 'secondary'
                                        "
                                    />
                                </template>
                            </Column>
                            <Column
                                header="Avaliar"
                                header-class="px-4 py-3 text-center w-32 whitespace-nowrap"
                                body-class="px-4 py-3 text-center w-32 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <ToggleSwitch
                                        v-model="data.pode_avaliar"
                                        @update:model-value="
                                            togglePermission(
                                                data,
                                                'pode_avaliar',
                                            )
                                        "
                                    />
                                </template>
                            </Column>
                            <Column
                                header="Resultados"
                                header-class="px-4 py-3 text-center w-32 whitespace-nowrap"
                                body-class="px-4 py-3 text-center w-32 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <ToggleSwitch
                                        v-model="
                                            data.pode_visualizar_resultados
                                        "
                                        @update:model-value="
                                            togglePermission(
                                                data,
                                                'pode_visualizar_resultados',
                                            )
                                        "
                                    />
                                </template>
                            </Column>
                            <Column
                                header="Baixar docs"
                                header-class="px-4 py-3 text-center w-32 whitespace-nowrap"
                                body-class="px-4 py-3 text-center w-32 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <ToggleSwitch
                                        v-model="data.pode_baixar_documentos"
                                        @update:model-value="
                                            togglePermission(
                                                data,
                                                'pode_baixar_documentos',
                                            )
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
                                        v-tooltip.left="'Remover atribuição'"
                                        rounded
                                        text
                                        severity="danger"
                                        icon="pi pi-trash"
                                        @click="
                                            confirmRemoveAssignment(data)
                                        "
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
