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
    destroy as destroyTipoTitulo,
    store as storeTipoTitulo,
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
const editingTituloId = ref<number | null>(null);
const calculoOptions = [
    { label: 'Data', value: 'data' },
    { label: 'Valor', value: 'valor' },
];

const tituloForm = useForm({
    descricao: '',
    status: true,
    calculo: 'data',
});

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

const startEditTitulo = (item: TipoTitulo): void => {
    editingTituloId.value = item.id;
    tituloForm.descricao = item.descricao;
    tituloForm.status = item.status;
    tituloForm.calculo = item.calculo ?? 'data';
};

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
            </div>

            <Card class="rounded-xl shadow-md">
                <template #content>
                    <form
                        class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_auto_auto]"
                        @submit.prevent="saveTitulo"
                    >
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
                            :label="editingTituloId ? 'Atualizar' : 'Adicionar'"
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
                                        {{ data.status ? 'Ativo' : 'Inativo' }}
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
</template>
