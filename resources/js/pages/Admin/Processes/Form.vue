<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Fieldset from 'primevue/fieldset';
import Fluid from 'primevue/fluid';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index, store, update } from '@/routes/admin/processes';

const props = defineProps<{ selectionProcess?: Record<string, unknown> }>();
const form = useForm({
    titulo: (props.selectionProcess?.titulo as string) ?? '',
    descricao: (props.selectionProcess?.descricao as string) ?? '',
    regras: (props.selectionProcess?.regras as string) ?? '',
    status: (props.selectionProcess?.status as string) ?? 'rascunho',
    inscricao_inicio_em:
        (props.selectionProcess?.inscricao_inicio_em as string) ?? '',
    inscricao_fim_em:
        (props.selectionProcess?.inscricao_fim_em as string) ?? '',
});

const clientErrors = ref<Record<string, string>>({});

const statusOptions = [
    { label: 'Rascunho', value: 'rascunho' },
    { label: 'Ativo', value: 'ativo' },
    { label: 'Encerrado', value: 'encerrado' },
];

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

function clearClientError(field: string): void {
    if (!clientErrors.value[field]) {
        return;
    }

    const next = { ...clientErrors.value };
    delete next[field];
    clientErrors.value = next;
}

function touchField(field: string): void {
    form.clearErrors(field);
    clearClientError(field);
}

function touchDateFields(): void {
    form.clearErrors('inscricao_inicio_em', 'inscricao_fim_em');
    clearClientError('inscricao_inicio_em');
    clearClientError('inscricao_fim_em');
}

function validateClient(): boolean {
    clientErrors.value = {};

    let valid = true;

    if (!form.titulo.trim()) {
        clientErrors.value.titulo = 'Este campo é obrigatório.';
        valid = false;
    }

    if (!form.descricao.trim()) {
        clientErrors.value.descricao = 'Este campo é obrigatório.';
        valid = false;
    }

    const start = form.inscricao_inicio_em;
    const end = form.inscricao_fim_em;

    if (start && end) {
        const startTime = new Date(start).getTime();
        const endTime = new Date(end).getTime();

        if (
            Number.isNaN(startTime) ||
            Number.isNaN(endTime) ||
            startTime > endTime
        ) {
            const msg =
                'A data de início não pode ser posterior à data de fim.';
            clientErrors.value.inscricao_inicio_em = msg;
            clientErrors.value.inscricao_fim_em = msg;
            valid = false;
        }
    }

    return valid;
}

const submit = (): void => {
    if (!validateClient()) {
        return;
    }

    if (props.selectionProcess?.id) {
        form.put(update(props.selectionProcess.id as number).url);

        return;
    }

    form.post(store().url);
};
</script>

<template>
    <div class="p-1">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Novo Processo"
                    description="Cadastre e organize as informações principais do processo."
                    :icon="FileText"
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
                            <Fieldset legend="Dados gerais">
                                <div class="flex flex-col gap-4">
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm"
                                            >Título
                                            <span class="text-red-600">*</span></span
                                        >
                                        <InputText
                                            v-model="form.titulo"
                                            placeholder="Título do processo"
                                            :invalid="fieldInvalid('titulo')"
                                            @update:model-value="touchField('titulo')"
                                        />
                                        <small
                                            v-if="errorMessage('titulo')"
                                            class="text-sm text-red-600"
                                            >{{ errorMessage('titulo') }}</small
                                        >
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm"
                                            >Descrição
                                            <span class="text-red-600">*</span></span
                                        >
                                        <Textarea
                                            v-model="form.descricao"
                                            rows="4"
                                            placeholder="Descrição"
                                            :invalid="fieldInvalid('descricao')"
                                            @update:model-value="
                                                touchField('descricao')
                                            "
                                        />
                                        <small
                                            v-if="errorMessage('descricao')"
                                            class="text-sm text-red-600"
                                            >{{ errorMessage('descricao') }}</small
                                        >
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">Regras</span>
                                        <Textarea
                                            v-model="form.regras"
                                            rows="5"
                                            placeholder="Regras do edital"
                                        />
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm"
                                            >Status
                                            <span class="text-red-600">*</span></span
                                        >
                                        <Select
                                            v-model="form.status"
                                            :options="statusOptions"
                                            option-label="label"
                                            option-value="value"
                                            :invalid="fieldInvalid('status')"
                                            @update:model-value="
                                                touchField('status')
                                            "
                                        />
                                        <small
                                            v-if="errorMessage('status')"
                                            class="text-sm text-red-600"
                                            >{{ errorMessage('status') }}</small
                                        >
                                    </label>
                                </div>
                            </Fieldset>

                            <Fieldset legend="Período de inscrição">
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm"
                                            >Início das inscrições</span
                                        >
                                        <InputText
                                            v-model="form.inscricao_inicio_em"
                                            type="datetime-local"
                                            :invalid="
                                                fieldInvalid(
                                                    'inscricao_inicio_em',
                                                )
                                            "
                                            @update:model-value="
                                                touchDateFields()
                                            "
                                        />
                                        <small
                                            v-if="
                                                errorMessage(
                                                    'inscricao_inicio_em',
                                                )
                                            "
                                            class="text-sm text-red-600"
                                            >{{
                                                errorMessage(
                                                    'inscricao_inicio_em',
                                                )
                                            }}</small
                                        >
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm"
                                            >Fim das inscrições</span
                                        >
                                        <InputText
                                            v-model="form.inscricao_fim_em"
                                            type="datetime-local"
                                            :invalid="
                                                fieldInvalid(
                                                    'inscricao_fim_em',
                                                )
                                            "
                                            @update:model-value="
                                                touchDateFields()
                                            "
                                        />
                                        <small
                                            v-if="
                                                errorMessage(
                                                    'inscricao_fim_em',
                                                )
                                            "
                                            class="text-sm text-red-600"
                                            >{{
                                                errorMessage(
                                                    'inscricao_fim_em',
                                                )
                                            }}</small
                                        >
                                    </label>
                                </div>
                            </Fieldset>

                            <Divider />

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
                                    label="Salvar processo"
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
