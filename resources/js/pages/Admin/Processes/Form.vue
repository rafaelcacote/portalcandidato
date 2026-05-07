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

const statusOptions = [
    { label: 'Rascunho', value: 'rascunho' },
    { label: 'Ativo', value: 'ativo' },
    { label: 'Encerrado', value: 'encerrado' },
];

const submit = (): void => {
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
                    />
                </Link>
            </div>

            <Card class="rounded-xl shadow-md">
                <template #content>
                    <Fluid>
                        <form class="flex flex-col gap-5 p-2 md:p-3" @submit.prevent="submit">
                            <Fieldset legend="Dados gerais">
                                <div class="flex flex-col gap-4">
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">Título</span>
                                        <InputText
                                            v-model="form.titulo"
                                            placeholder="Título do processo"
                                        />
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">Descrição</span>
                                        <Textarea
                                            v-model="form.descricao"
                                            rows="4"
                                            placeholder="Descrição"
                                        />
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
                                        <span class="text-sm">Status</span>
                                        <Select
                                            v-model="form.status"
                                            :options="statusOptions"
                                            option-label="label"
                                            option-value="value"
                                        />
                                    </label>
                                </div>
                            </Fieldset>

                            <Fieldset legend="Período de inscrição">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm"
                                            >Início das inscrições</span
                                        >
                                        <InputText
                                            v-model="form.inscricao_inicio_em"
                                            type="datetime-local"
                                        />
                                    </label>
                                    <label class="flex flex-col gap-2">
                                        <span class="text-sm">Fim das inscrições</span>
                                        <InputText
                                            v-model="form.inscricao_fim_em"
                                            type="datetime-local"
                                        />
                                    </label>
                                </div>
                            </Fieldset>

                            <Divider />

                            <div class="mt-2 flex w-full items-center justify-end gap-2">
                                <Link :href="index().url">
                                    <Button
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
