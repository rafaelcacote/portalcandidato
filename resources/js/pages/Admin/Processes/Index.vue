<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import Button from 'primevue/button';
import ButtonGroup from 'primevue/buttongroup';
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
import { formatDateTimeBR } from '@/lib/utils';
import { create, destroy, edit, show } from '@/routes/admin/processes';

const props = defineProps<{
    processes: {
        data: Array<{
            id: number;
            titulo: string;
            status: string;
            inscricao_inicio_em?: string | null;
            inscricao_fim_em?: string | null;
        }>;
    };
}>();

const statusSeverity: Record<
    string,
    'secondary' | 'success' | 'warn' | 'danger'
> = {
    rascunho: 'secondary',
    ativo: 'success',
    encerrado: 'warn',
};

const confirm = useConfirm();
const vTooltip = Tooltip;
const searchTerm = ref<string>('');
const selectedStatus = ref<string | null>(null);

const statusOptions = [
    { label: 'Todos status', value: null },
    { label: 'Rascunho', value: 'rascunho' },
    { label: 'Ativo', value: 'ativo' },
    { label: 'Encerrado', value: 'encerrado' },
];

const filteredProcesses = computed(() => {
    return props.processes.data.filter((process) => {
        const matchesSearch = process.titulo
            .toLowerCase()
            .includes(searchTerm.value.toLowerCase());
        const matchesStatus = selectedStatus.value
            ? process.status === selectedStatus.value
            : true;

        return matchesSearch && matchesStatus;
    });
});

const removeProcess = (id: number): void => {
    router.delete(destroy(id).url);
};

const confirmRemoveProcess = (id: number): void => {
    confirm.require({
        header: 'Confirmar exclusão',
        message: 'Tem certeza que deseja excluir este processo?',
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
            removeProcess(id);
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
                    title="Processos Seletivos"
                    description="Gerencie os processos cadastrados na plataforma."
                    :icon="FileText"
                />
                <Link :href="create().url">
                    <Button
                        v-tooltip.bottom="'Novo processo'"
                        label="Novo processo"
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
                            placeholder="Buscar por título do processo"
                        />
                        <Select
                            v-model="selectedStatus"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Todos status"
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
                        :value="filteredProcesses"
                        striped-rows
                        class="w-full"
                        table-style="min-width: 50rem; width: 100%; table-layout: fixed"
                    >
                        <template #empty>
                            <div
                                class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-inbox text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum processo cadastrado
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Ainda nao existem processos seletivos
                                    registrados. Clique em "Novo processo" para
                                    criar o primeiro.
                                </p>
                                <Link :href="create().url">
                                    <Button
                                        label="Novo processo"
                                        icon="pi pi-plus"
                                        size="small"
                                    />
                                </Link>
                            </div>
                        </template>

                        <Column
                            field="id"
                            header="ID"
                            header-class="px-4 py-3 w-16 whitespace-nowrap"
                            body-class="px-4 py-3 w-16 whitespace-nowrap"
                        />
                        <Column
                            field="titulo"
                            header="Título"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        />
                        <Column
                            header="Início Inscrição"
                            header-class="px-4 py-3 whitespace-nowrap"
                            body-class="px-4 py-3 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                {{ formatDateTimeBR(data.inscricao_inicio_em) }}
                            </template>
                        </Column>
                        <Column
                            header="Fim Inscrição"
                            header-class="px-4 py-3 whitespace-nowrap"
                            body-class="px-4 py-3 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                {{ formatDateTimeBR(data.inscricao_fim_em) }}
                            </template>
                        </Column>
                        <Column
                            header="Status"
                            header-class="px-4 py-3 w-32 whitespace-nowrap"
                            body-class="px-4 py-3 w-32 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :value="data.status"
                                    :severity="
                                        statusSeverity[data.status] ??
                                        'secondary'
                                    "
                                />
                            </template>
                        </Column>
                        <Column
                            header="Ações"
                            header-class="px-4 py-3 text-end w-40 whitespace-nowrap"
                            body-class="px-4 py-3 text-end w-40 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                <div class="flex justify-end">
                                    <ButtonGroup>
                                        <Link :href="edit(data.id).url">
                                            <Button
                                                v-tooltip.left="
                                                    'Editar processo'
                                                "
                                                rounded
                                                text
                                                icon="pi pi-pencil"
                                                class="h-9 w-9"
                                                aria-label="Editar processo"
                                            />
                                        </Link>
                                        <Link :href="show(data.id).url">
                                            <Button
                                                v-tooltip.left="
                                                    'Configurar processo'
                                                "
                                                rounded
                                                text
                                                icon="pi pi-cog"
                                                class="h-9 w-9"
                                                aria-label="Configurar processo"
                                            />
                                        </Link>
                                        <Button
                                            v-tooltip.left="'Excluir processo'"
                                            rounded
                                            text
                                            severity="danger"
                                            icon="pi pi-trash"
                                            class="h-9 w-9"
                                            aria-label="Excluir processo"
                                            @click="
                                                confirmRemoveProcess(data.id)
                                            "
                                        />
                                    </ButtonGroup>
                                </div>
                            </template>
                        </Column>

                        <template #footer>
                            <div
                                class="px-2 py-3 text-sm text-muted-foreground"
                            >
                                Total: {{ filteredProcesses.length }}
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
