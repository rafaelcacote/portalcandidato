<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Settings2 } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Fieldset from 'primevue/fieldset';
import Fluid from 'primevue/fluid';
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import {
    store as storeTipoDocumento,
    update as updateTipoDocumento,
} from '@/routes/admin/processes/types/documentos';
import { index } from '@/routes/admin/support-tables/document-types';

const props = defineProps<{
    tipoDocumento?: { id: number; descricao: string; status: boolean };
}>();

const form = useForm({
    descricao: props.tipoDocumento?.descricao ?? '',
    status: props.tipoDocumento?.status ?? true,
});

const clientErrors = ref<Record<string, string>>({});

const isEditing = (): boolean => Boolean(props.tipoDocumento?.id);

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

    if (!form.descricao.trim()) {
        clientErrors.value.descricao = 'Este campo é obrigatório.';
        valid = false;
    } else if (form.descricao.length > 255) {
        clientErrors.value.descricao =
            'A descrição não pode ter mais de 255 caracteres.';
        valid = false;
    }

    return valid;
}

const submit = (): void => {
    if (!validateClient()) {
        return;
    }

    if (props.tipoDocumento?.id) {
        form.put(updateTipoDocumento(props.tipoDocumento.id).url);

        return;
    }

    form.post(storeTipoDocumento().url);
};
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 lg:px-10 md:py-4">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    :title="
                        isEditing()
                            ? 'Editar tipo de documento'
                            : 'Novo tipo de documento'
                    "
                    description="Informe a descrição e se o tipo está ativo."
                    :icon="Settings2"
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
                            <Fieldset legend="Dados do tipo">
                                <div class="flex flex-col gap-4">
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm"
                                            >Descrição
                                            <span class="text-red-600">*</span></span
                                        >
                                        <InputText
                                            v-model="form.descricao"
                                            placeholder="Descrição do tipo de documento"
                                            :invalid="fieldInvalid('descricao')"
                                            maxlength="255"
                                            @update:model-value="
                                                touchField('descricao')
                                            "
                                        />
                                        <small
                                            v-if="errorMessage('descricao')"
                                            class="text-sm text-red-600"
                                            >{{
                                                errorMessage('descricao')
                                            }}</small
                                        >
                                    </label>
                                    <div
                                        class="flex max-w-xs items-center gap-3 rounded border px-3 py-2"
                                    >
                                        <ToggleSwitch
                                            v-model="form.status"
                                            @update:model-value="
                                                touchField('status')
                                            "
                                        />
                                        <span class="text-sm">Ativo</span>
                                    </div>
                                    <small
                                        v-if="errorMessage('status')"
                                        class="text-sm text-red-600"
                                        >{{ errorMessage('status') }}</small
                                    >
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
                                    label="Salvar tipo de documento"
                                    icon="pi pi-check"
                                    :loading="form.processing"
                                    class="w-auto px-3"
                                />
                            </div>
                        </form>
                    </Fluid>
                </template>
            </Card>
        </div>
    </div>
</template>
