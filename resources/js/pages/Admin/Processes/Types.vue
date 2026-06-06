<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Settings2 } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';
import Heading from '@/components/Heading.vue';
import {
    destroy as destroyTipoDocumento,
    update as updateTipoDocumento,
} from '@/routes/admin/processes/types/documentos';
import {
    destroy as destroyTipoTitulo,
    update as updateTipoTitulo,
} from '@/routes/admin/processes/types/titulos';
import {
    create as createDocumentTypePage,
    edit as editDocumentTypePage,
} from '@/routes/admin/support-tables/document-types';
import {
    create as createTitleTypePage,
    edit as editTitleTypePage,
} from '@/routes/admin/support-tables/title-types';

type TipoDocumento = {
    id: number;
    descricao: string;
    status: boolean;
};

type TipoTitulo = {
    id: number;
    descricao: string;
    status: boolean;
    calculo?: string | null;
};

const props = defineProps<{
    tiposDocumento: TipoDocumento[];
    tiposTitulo: TipoTitulo[];
}>();

const vTooltip = Tooltip;

const deleteDocumento = (id: number): void => {
    router.delete(destroyTipoDocumento(id).url, { preserveScroll: true });
};

const deleteTitulo = (id: number): void => {
    router.delete(destroyTipoTitulo(id).url, { preserveScroll: true });
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
    <div class="px-4 py-3 sm:px-6 md:px-8 md:py-4 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <ConfirmDialog />

            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Tipos de Processo"
                    description="Cadastre os tipos reutilizáveis de documentos e títulos."
                    :icon="Settings2"
                />
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <Card class="rounded-xl shadow-md">
                    <template #title>
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 pr-2"
                        >
                            <span>Tipos de Documento</span>
                            <Link :href="createDocumentTypePage().url">
                                <Button
                                    v-tooltip.bottom="'Novo tipo de documento'"
                                    label="Novo"
                                    icon="pi pi-plus"
                                    size="small"
                                />
                            </Link>
                        </div>
                    </template>
                    <template #content>
                        <DataTable
                            :value="props.tiposDocumento"
                            striped-rows
                            class="w-full"
                            table-style="width: 100%; table-layout: fixed"
                        >
                            <Column
                                header="Status"
                                header-class="px-4 py-3 whitespace-nowrap"
                                body-class="px-4 py-3 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <ToggleSwitch
                                            v-model="data.status"
                                            @update:model-value="
                                                toggleDocumentoStatus(data)
                                            "
                                        />
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                data.status
                                                    ? 'Ativo'
                                                    : 'Inativo'
                                            }}
                                        </span>
                                    </div>
                                </template>
                            </Column>
                            <Column
                                field="descricao"
                                header="Descrição"
                                header-class="px-4 py-3 text-end min-w-0"
                                body-class="px-4 py-3 text-end min-w-0"
                            />
                            <Column
                                header="Ações"
                                header-class="px-4 py-3 text-end w-32 whitespace-nowrap"
                                body-class="px-4 py-3 text-end w-32 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <div class="flex justify-end gap-1">
                                        <Link
                                            :href="
                                                editDocumentTypePage(data.id)
                                                    .url
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
                        </DataTable>
                    </template>
                </Card>

                <Card class="rounded-xl shadow-md">
                    <template #title>
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 pr-2"
                        >
                            <span>Tipos de Título</span>
                            <Link :href="createTitleTypePage().url">
                                <Button
                                    v-tooltip.bottom="'Novo tipo de título'"
                                    label="Novo"
                                    icon="pi pi-plus"
                                    size="small"
                                />
                            </Link>
                        </div>
                    </template>
                    <template #content>
                        <DataTable
                            :value="props.tiposTitulo"
                            striped-rows
                            class="w-full"
                            table-style="width: 100%; table-layout: fixed"
                        >
                            <Column
                                header="Cálculo"
                                header-class="px-4 py-3 whitespace-nowrap"
                                body-class="px-4 py-3 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    {{
                                        data.calculo === 'valor'
                                            ? 'Valor'
                                            : 'Data'
                                    }}
                                </template>
                            </Column>
                            <Column
                                header="Status"
                                header-class="px-4 py-3 whitespace-nowrap"
                                body-class="px-4 py-3 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <ToggleSwitch
                                            v-model="data.status"
                                            @update:model-value="
                                                toggleTituloStatus(data)
                                            "
                                        />
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                data.status
                                                    ? 'Ativo'
                                                    : 'Inativo'
                                            }}
                                        </span>
                                    </div>
                                </template>
                            </Column>
                            <Column
                                field="descricao"
                                header="Descrição"
                                header-class="px-4 py-3 text-end min-w-0"
                                body-class="px-4 py-3 text-end min-w-0"
                            />
                            <Column
                                header="Ações"
                                header-class="px-4 py-3 text-end w-32 whitespace-nowrap"
                                body-class="px-4 py-3 text-end w-32 whitespace-nowrap"
                            >
                                <template #body="{ data }">
                                    <div class="flex justify-end gap-1">
                                        <Link
                                            :href="
                                                editTitleTypePage(data.id).url
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
                                            @click="deleteTitulo(data.id)"
                                        />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>
