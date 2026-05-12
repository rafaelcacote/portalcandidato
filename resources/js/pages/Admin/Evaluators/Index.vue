<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Users } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import {
    create as createEvaluatorPage,
    destroy as destroyEvaluator,
    edit as editEvaluatorPage,
} from '@/routes/admin/evaluators';

type Evaluator = {
    id: number;
    name: string;
    email: string;
    cpf?: string | null;
    telefone?: string | null;
    ativo: boolean;
    evaluator_assignments_count: number;
};

const props = defineProps<{
    evaluators: Evaluator[];
}>();

const vTooltip = Tooltip;
const confirm = useConfirm();

const searchTerm = ref<string>('');
const selectedStatus = ref<boolean | null>(null);

const statusFilterOptions = [
    { label: 'Todos os status', value: null },
    { label: 'Ativo', value: true },
    { label: 'Inativo', value: false },
];

const filteredEvaluators = computed(() => {
    return props.evaluators.filter((evaluator) => {
        const matchesSearch =
            evaluator.name
                .toLowerCase()
                .includes(searchTerm.value.toLowerCase()) ||
            evaluator.email
                .toLowerCase()
                .includes(searchTerm.value.toLowerCase());
        const matchesStatus =
            selectedStatus.value !== null
                ? evaluator.ativo === selectedStatus.value
                : true;

        return matchesSearch && matchesStatus;
    });
});

const hasNoRecords = computed(() => props.evaluators.length === 0);

const removeEvaluator = (id: number): void => {
    router.delete(destroyEvaluator(id).url, { preserveScroll: true });
};

const confirmRemoveEvaluator = (evaluator: Evaluator): void => {
    confirm.require({
        header: 'Confirmar exclusão',
        message: `Deseja realmente excluir o avaliador "${evaluator.name}"? Todas as atribuições a processos serão removidas.`,
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Excluir',
        rejectProps: {
            outlined: true,
            icon: 'pi pi-times',
        },
        acceptProps: {
            severity: 'danger',
            icon: 'pi pi-trash',
        },
        accept: () => {
            removeEvaluator(evaluator.id);
        },
    });
};
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 lg:px-10 md:py-4">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <ConfirmDialog />

            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Avaliadores"
                    description="Cadastre avaliadores e gerencie suas atribuições aos processos."
                    :icon="Users"
                />
                <Link :href="createEvaluatorPage().url">
                    <Button
                        v-tooltip.bottom="'Novo avaliador'"
                        label="Novo Avaliador"
                        icon="pi pi-plus"
                        size="small"
                    />
                </Link>
            </div>

            <Card class="overflow-hidden rounded-xl shadow-md">
                <template #content>
                    <div
                        class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-[1fr_220px_auto]"
                    >
                        <InputText
                            v-model="searchTerm"
                            placeholder="Buscar por nome ou e-mail"
                        />
                        <Select
                            v-model="selectedStatus"
                            :options="statusFilterOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Todos os status"
                        />
                        <Button
                            v-tooltip.bottom="'Limpar filtros'"
                            label="Limpar"
                            severity="secondary"
                            outlined
                            size="small"
                            @click="
                                searchTerm = '';
                                selectedStatus = null;
                            "
                        />
                    </div>

                    <DataTable
                        :value="filteredEvaluators"
                        striped-rows
                        class="w-full"
                        table-style="width: 100%; table-layout: fixed"
                    >
                        <template #empty>
                            <div
                                v-if="hasNoRecords"
                                class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-users text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum avaliador cadastrado
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Ainda não existem avaliadores registrados.
                                    Clique em Novo Avaliador para cadastrar o
                                    primeiro.
                                </p>
                                <Link :href="createEvaluatorPage().url">
                                    <Button
                                        label="Novo Avaliador"
                                        icon="pi pi-plus"
                                        size="small"
                                    />
                                </Link>
                            </div>
                            <div
                                v-else
                                class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-filter-slash text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum resultado com estes filtros
                                </p>
                                <Button
                                    label="Limpar filtros"
                                    icon="pi pi-times"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    @click="
                                        searchTerm = '';
                                        selectedStatus = null;
                                    "
                                />
                            </div>
                        </template>

                        <Column
                            field="name"
                            header="Nome"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        />
                        <Column
                            field="email"
                            header="E-mail"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        />
                        <Column
                            header="Telefone"
                            header-class="px-4 py-3 whitespace-nowrap"
                            body-class="px-4 py-3 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                {{ data.telefone ?? '-' }}
                            </template>
                        </Column>
                        <Column
                            header="Processos"
                            header-class="px-4 py-3 text-center w-32 whitespace-nowrap"
                            body-class="px-4 py-3 text-center w-32 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :value="data.evaluator_assignments_count"
                                    severity="info"
                                />
                            </template>
                        </Column>
                        <Column
                            header="Status"
                            header-class="px-4 py-3 w-28 whitespace-nowrap"
                            body-class="px-4 py-3 w-28 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :value="data.ativo ? 'Ativo' : 'Inativo'"
                                    :severity="
                                        data.ativo ? 'success' : 'secondary'
                                    "
                                />
                            </template>
                        </Column>
                        <Column
                            header="Ações"
                            header-class="px-4 py-3 text-end w-32 whitespace-nowrap"
                            body-class="px-4 py-3 text-end w-32 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                <div class="flex justify-end gap-1">
                                    <Link
                                        :href="editEvaluatorPage(data.id).url"
                                        class="inline-flex"
                                    >
                                        <Button
                                            v-tooltip.left="
                                                'Editar e gerenciar atribuições'
                                            "
                                            rounded
                                            text
                                            icon="pi pi-pencil"
                                        />
                                    </Link>
                                    <Button
                                        v-tooltip.left="'Excluir'"
                                        rounded
                                        text
                                        severity="danger"
                                        icon="pi pi-trash"
                                        @click="confirmRemoveEvaluator(data)"
                                    />
                                </div>
                            </template>
                        </Column>

                        <template #footer>
                            <div
                                class="px-2 py-3 text-sm text-muted-foreground"
                            >
                                Exibindo {{ filteredEvaluators.length }} de
                                {{ props.evaluators.length }}
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
