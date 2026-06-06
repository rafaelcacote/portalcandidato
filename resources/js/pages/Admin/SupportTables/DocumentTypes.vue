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
    destroy as destroyTipoDocumento,
    update as updateTipoDocumento,
} from '@/routes/admin/processes/types/documentos';
import {
    create as createDocumentTypePage,
    edit as editDocumentTypePage,
} from '@/routes/admin/support-tables/document-types';

type TipoDocumento = {
    id: number;
    descricao: string;
    status: boolean;
};

const props = defineProps<{
    tiposDocumento: TipoDocumento[];
}>();

const vTooltip = Tooltip;

const searchTerm = ref<string>('');
const selectedStatus = ref<boolean | null>(null);

const statusFilterOptions = [
    { label: 'Todos os status', value: null },
    { label: 'Ativo', value: true },
    { label: 'Inativo', value: false },
];

const filteredTipos = computed(() => {
    return props.tiposDocumento.filter((item) => {
        const matchesSearch = item.descricao
            .toLowerCase()
            .includes(searchTerm.value.toLowerCase());
        const matchesStatus =
            selectedStatus.value !== null
                ? item.status === selectedStatus.value
                : true;

        return matchesSearch && matchesStatus;
    });
});

const hasNoRecords = computed(() => props.tiposDocumento.length === 0);

const deleteDocumento = (id: number): void => {
    router.delete(destroyTipoDocumento(id).url, { preserveScroll: true });
};

const toggleDocumentoStatus = (item: TipoDocumento): void => {
    router.put(
        updateTipoDocumento(item.id).url,
        {
            descricao: item.descricao,
            status: item.status,
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 md:py-4 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <ConfirmDialog />

            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Tipos de Documentos"
                    description="Cadastre os tipos reutilizáveis de documentos."
                    :icon="Settings2"
                />
                <Link :href="createDocumentTypePage().url">
                    <Button
                        v-tooltip.bottom="'Novo documento'"
                        label="Novo Documento"
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
                            placeholder="Buscar por descrição"
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
                                    Nenhum tipo de documento cadastrado
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Ainda não existem tipos de documento
                                    registrados. Clique em Novo Documento para
                                    criar o primeiro.
                                </p>
                                <Link :href="createDocumentTypePage().url">
                                    <Button
                                        label="Novo Documento"
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
                                    Ajuste a busca ou o filtro de status, ou
                                    limpe os filtros para ver todos os
                                    registros.
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
                            field="descricao"
                            header="Descrição"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        />
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
                                            toggleDocumentoStatus(data)
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
                                        :href="
                                            editDocumentTypePage(data.id).url
                                        "
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
                                        @click="deleteDocumento(data.id)"
                                    />
                                </div>
                            </template>
                        </Column>

                        <template #footer>
                            <div
                                class="px-2 py-3 text-sm text-muted-foreground"
                            >
                                Exibindo {{ filteredTipos.length }} de
                                {{ props.tiposDocumento.length }}
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
