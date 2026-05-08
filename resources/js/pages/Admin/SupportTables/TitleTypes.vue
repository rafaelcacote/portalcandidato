<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Settings2 } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import {
    create as createTitleTypePage,
    edit as editTitleTypePage,
} from '@/routes/admin/support-tables/title-types';
import {
    destroy as destroyTipoTitulo,
    update as updateTipoTitulo,
} from '@/routes/admin/processes/types/titulos';

type TipoTitulo = {
    id: number;
    descricao: string;
    status: boolean;
    calculo?: string | null;
};

const props = defineProps<{
    tiposTitulo: TipoTitulo[];
}>();

const vTooltip = Tooltip;

const searchTerm = ref<string>('');
const selectedStatus = ref<boolean | null>(null);
const selectedCalculo = ref<string | null>(null);

const statusFilterOptions = [
    { label: 'Todos os status', value: null },
    { label: 'Ativo', value: true },
    { label: 'Inativo', value: false },
];

const calculoFilterOptions = [
    { label: 'Todas as regras', value: null },
    { label: 'Data', value: 'data' },
    { label: 'Valor', value: 'valor' },
];

const filteredTipos = computed(() => {
    return props.tiposTitulo.filter((item) => {
        const matchesSearch = item.descricao
            .toLowerCase()
            .includes(searchTerm.value.toLowerCase());
        const matchesStatus =
            selectedStatus.value !== null
                ? item.status === selectedStatus.value
                : true;
        const calculo = item.calculo ?? 'data';
        const matchesCalculo =
            selectedCalculo.value !== null
                ? calculo === selectedCalculo.value
                : true;

        return matchesSearch && matchesStatus && matchesCalculo;
    });
});

const hasNoRecords = computed(() => props.tiposTitulo.length === 0);

const deleteTitulo = (id: number): void => {
    router.delete(destroyTipoTitulo(id).url, { preserveScroll: true });
};

const toggleTituloStatus = (item: TipoTitulo): void => {
    router.put(
        updateTipoTitulo(item.id).url,
        {
            descricao: item.descricao,
            status: item.status,
            calculo: item.calculo ?? 'data',
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <div class="p-1">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <ConfirmDialog />

            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Tipos de Títulos"
                    description="Cadastre os tipos reutilizáveis de títulos."
                    :icon="Settings2"
                />
                <Link :href="createTitleTypePage().url">
                    <Button
                        v-tooltip.bottom="'Novo título'"
                        label="Novo Título"
                        icon="pi pi-plus"
                        size="small"
                    />
                </Link>
            </div>

            <Card class="overflow-hidden rounded-xl shadow-md">
                <template #content>
                    <div
                        class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-[1fr_160px_180px_auto]"
                    >
                        <InputText
                            v-model="searchTerm"
                            placeholder="Buscar por descrição"
                        />
                        <Select
                            v-model="selectedCalculo"
                            :options="calculoFilterOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Regra de cálculo"
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
                                selectedCalculo = null;
                            "
                        />
                    </div>

                    <DataTable
                        :value="filteredTipos"
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
                                    class="pi pi-inbox text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum tipo de título cadastrado
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Ainda não existem tipos de título
                                    registrados. Clique em Novo Título para
                                    criar o primeiro.
                                </p>
                                <Link :href="createTitleTypePage().url">
                                    <Button
                                        label="Novo Título"
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
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Ajuste a busca ou os filtros, ou limpe para
                                    ver todos os registros.
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
                                        selectedCalculo = null;
                                    "
                                />
                            </div>
                        </template>

                        <Column
                            field="descricao"
                            header="Descrição"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        />
                        <Column
                            header="Cálculo"
                            header-class="px-4 py-3 w-32 whitespace-nowrap"
                            body-class="px-4 py-3 w-32 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                {{
                                    data.calculo === 'valor' ? 'Valor' : 'Data'
                                }}
                            </template>
                        </Column>
                        <Column
                            header="Status"
                            header-class="px-4 py-3 w-40 whitespace-nowrap"
                            body-class="px-4 py-3 w-40 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <ToggleSwitch
                                        v-model="data.status"
                                        @update:model-value="
                                            toggleTituloStatus(data)
                                        "
                                    />
                                    <span class="text-xs text-muted-foreground">
                                        {{ data.status ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </div>
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
                                        :href="editTitleTypePage(data.id).url"
                                        class="inline-flex"
                                    >
                                        <Button
                                            v-tooltip.left="'Editar'"
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
                                        @click="deleteTitulo(data.id)"
                                    />
                                </div>
                            </template>
                        </Column>

                        <template #footer>
                            <div
                                class="px-2 py-3 text-sm text-muted-foreground"
                            >
                                Exibindo {{ filteredTipos.length }} de
                                {{ props.tiposTitulo.length }}
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
