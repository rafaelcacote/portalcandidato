<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Settings2 } from 'lucide-vue-next';
import Button from 'primevue/button';
import ButtonGroup from 'primevue/buttongroup';
import Card from 'primevue/card';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import {
    destroy as destroyTipoDocumento,
    store as storeTipoDocumento,
    update as updateTipoDocumento,
} from '@/routes/admin/processes/types/documentos';
import {
    destroy as destroyTipoTitulo,
    store as storeTipoTitulo,
    update as updateTipoTitulo,
} from '@/routes/admin/processes/types/titulos';

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
const editingDocumentoId = ref<number | null>(null);
const editingTituloId = ref<number | null>(null);
const calculoOptions = [
    { label: 'Data', value: 'data' },
    { label: 'Valor', value: 'valor' },
];

const documentoForm = useForm({
    descricao: '',
    status: true,
});

const tituloForm = useForm({
    descricao: '',
    status: true,
    calculo: 'data',
});

const saveDocumento = (): void => {
    if (editingDocumentoId.value) {
        documentoForm.put(updateTipoDocumento(editingDocumentoId.value).url, {
            preserveScroll: true,
            onSuccess: () => {
                documentoForm.reset();
                editingDocumentoId.value = null;
            },
        });

        return;
    }

    documentoForm.post(storeTipoDocumento().url, {
        preserveScroll: true,
        onSuccess: () => documentoForm.reset(),
    });
};

const saveTitulo = (): void => {
    if (editingTituloId.value) {
        tituloForm.put(updateTipoTitulo(editingTituloId.value).url, {
            preserveScroll: true,
            onSuccess: () => {
                tituloForm.reset();
                editingTituloId.value = null;
            },
        });

        return;
    }

    tituloForm.post(storeTipoTitulo().url, {
        preserveScroll: true,
        onSuccess: () => tituloForm.reset(),
    });
};

const startEditDocumento = (item: TipoDocumento): void => {
    editingDocumentoId.value = item.id;
    documentoForm.descricao = item.descricao;
    documentoForm.status = item.status;
};

const startEditTitulo = (item: TipoTitulo): void => {
    editingTituloId.value = item.id;
    tituloForm.descricao = item.descricao;
    tituloForm.status = item.status;
    tituloForm.calculo = item.calculo ?? 'data';
};

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
    <div class="p-1">
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
                    <template #title>Tipos de Documento</template>
                    <template #content>
                        <form class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto_auto]" @submit.prevent="saveDocumento">
                            <InputText
                                v-model="documentoForm.descricao"
                                placeholder="Descrição do tipo de documento"
                            />
                            <div class="flex items-center gap-2 rounded border px-3 py-2">
                                <ToggleSwitch v-model="documentoForm.status" />
                                <span class="text-sm">Ativo</span>
                            </div>
                            <Button
                                type="submit"
                                :label="
                                    editingDocumentoId
                                        ? 'Atualizar'
                                        : 'Adicionar'
                                "
                                icon="pi pi-check"
                            />
                        </form>

                        <DataTable :value="props.tiposDocumento" striped-rows>
                            <Column
                                field="descricao"
                                header="Descrição"
                                header-class="px-4 py-3"
                                body-class="px-4 py-3"
                            />
                            <Column
                                header="Status"
                                header-class="px-4 py-3"
                                body-class="px-4 py-3"
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
                                header="Ações"
                                header-class="px-4 py-3"
                                body-class="px-4 py-3"
                            >
                                <template #body="{ data }">
                                    <ButtonGroup>
                                        <Button
                                            v-tooltip.left="'Editar'"
                                            rounded
                                            text
                                            icon="pi pi-pencil"
                                            @click="startEditDocumento(data)"
                                        />
                                        <Button
                                            v-tooltip.left="'Excluir'"
                                            rounded
                                            text
                                            severity="danger"
                                            icon="pi pi-trash"
                                            @click="deleteDocumento(data.id)"
                                        />
                                    </ButtonGroup>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <Card class="rounded-xl shadow-md">
                    <template #title>Tipos de Título</template>
                    <template #content>
                        <form class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_auto_auto]" @submit.prevent="saveTitulo">
                            <InputText
                                v-model="tituloForm.descricao"
                                placeholder="Descrição do tipo de título"
                            />
                            <Select
                                v-model="tituloForm.calculo"
                                :options="calculoOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Regra de cálculo"
                            />
                            <div class="flex items-center gap-2 rounded border px-3 py-2">
                                <ToggleSwitch v-model="tituloForm.status" />
                                <span class="text-sm">Ativo</span>
                            </div>
                            <Button
                                type="submit"
                                :label="
                                    editingTituloId ? 'Atualizar' : 'Adicionar'
                                "
                                icon="pi pi-check"
                            />
                        </form>

                        <DataTable :value="props.tiposTitulo" striped-rows>
                            <Column
                                field="descricao"
                                header="Descrição"
                                header-class="px-4 py-3"
                                body-class="px-4 py-3"
                            />
                            <Column
                                header="Cálculo"
                                header-class="px-4 py-3"
                                body-class="px-4 py-3"
                            >
                                <template #body="{ data }">
                                    {{ data.calculo === 'valor' ? 'Valor' : 'Data' }}
                                </template>
                            </Column>
                            <Column
                                header="Status"
                                header-class="px-4 py-3"
                                body-class="px-4 py-3"
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
                                header="Ações"
                                header-class="px-4 py-3"
                                body-class="px-4 py-3"
                            >
                                <template #body="{ data }">
                                    <ButtonGroup>
                                        <Button
                                            v-tooltip.left="'Editar'"
                                            rounded
                                            text
                                            icon="pi pi-pencil"
                                            @click="startEditTitulo(data)"
                                        />
                                        <Button
                                            v-tooltip.left="'Excluir'"
                                            rounded
                                            text
                                            severity="danger"
                                            icon="pi pi-trash"
                                            @click="deleteTitulo(data.id)"
                                        />
                                    </ButtonGroup>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>
