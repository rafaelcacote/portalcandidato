<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Select from 'primevue/select';
import { computed, reactive, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as reportsIndex } from '@/routes/admin/reports';
import { index as missingResearchLinesIndex } from '@/routes/admin/temporary/missing-research-lines';
import { update as missingResearchLineUpdate } from '@/routes/admin/temporary/missing-research-lines/applications';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel administrativo', href: adminDashboard.url() },
            { title: 'Relatórios', href: reportsIndex().url },
            {
                title: 'Linhas pendentes (temporário)',
                href: missingResearchLinesIndex().url,
            },
            { title: 'Atualizar candidatos', href: '#' },
        ],
    },
});

const props = defineProps<{
    selectionProcess: {
        id: number;
        titulo: string;
        status: string;
    };
    applications: Array<{
        id: number;
        numero_protocolo: string | null;
        nome_completo: string | null;
    }>;
    researchLineOptions: {
        lines: Array<{ value: string; label: string }>;
        advisors: Record<string, string[]>;
    };
}>();

type RowForm = {
    linha_pesquisa: string;
    orientador: string;
    processing: boolean;
};

const forms = reactive<Record<number, RowForm>>({});

props.applications.forEach((application) => {
    forms[application.id] = {
        linha_pesquisa: '',
        orientador: '',
        processing: false,
    };
});

const lineOptions = computed(() => props.researchLineOptions.lines);

const advisorOptionsForLine = (line: string) => {
    const advisors = props.researchLineOptions.advisors[line] ?? [];

    return advisors.map((name) => ({
        label: name,
        value: name,
    }));
};

watch(
    () => props.applications,
    (applications) => {
        applications.forEach((application) => {
            if (!forms[application.id]) {
                forms[application.id] = {
                    linha_pesquisa: '',
                    orientador: '',
                    processing: false,
                };
            }
        });
    },
    { deep: true },
);

const onLineChange = (applicationId: number): void => {
    const form = forms[applicationId];
    const advisors = props.researchLineOptions.advisors[form.linha_pesquisa] ?? [];

    if (!advisors.includes(form.orientador)) {
        form.orientador = '';
    }
};

const canSave = (applicationId: number): boolean => {
    const form = forms[applicationId];

    return form.linha_pesquisa !== '' && form.orientador !== '' && !form.processing;
};

const saveRow = (applicationId: number): void => {
    const form = forms[applicationId];

    if (!canSave(applicationId)) {
        return;
    }

    form.processing = true;

    router.put(
        missingResearchLineUpdate(applicationId).url,
        {
            linha_pesquisa: form.linha_pesquisa,
            orientador: form.orientador,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                form.processing = false;
            },
        },
    );
};
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 md:py-4 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <Message severity="warn" :closable="false">
                Ferramenta temporária. Após atualizar todos os candidatos,
                remova este fluxo do sistema.
            </Message>

            <div
                class="flex flex-col gap-4 py-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <Heading
                    title="Atualizar linha de pesquisa"
                    :description="selectionProcess.titulo"
                    :icon="AlertTriangle"
                />
                <Link :href="missingResearchLinesIndex().url">
                    <Button
                        label="Voltar"
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        outlined
                        size="small"
                    />
                </Link>
            </div>

            <Card class="overflow-hidden rounded-xl shadow-md">
                <template #content>
                    <DataTable
                        :value="applications"
                        striped-rows
                        class="w-full"
                        table-style="min-width: 72rem; width: 100%; table-layout: fixed"
                    >
                        <template #empty>
                            <div
                                class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-check-circle text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum candidato pendente neste processo
                                </p>
                            </div>
                        </template>

                        <Column
                            field="numero_protocolo"
                            header="Cod. inscrição"
                            header-class="px-4 py-3 w-36 whitespace-nowrap"
                            body-class="px-4 py-3 w-36 whitespace-nowrap"
                        />
                        <Column
                            field="nome_completo"
                            header="Nome completo"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        />
                        <Column
                            header="Linha de pesquisa"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        >
                            <template #body="{ data }">
                                <Select
                                    v-model="forms[data.id].linha_pesquisa"
                                    :options="lineOptions"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Selecione a linha"
                                    class="w-full"
                                    @update:model-value="onLineChange(data.id)"
                                />
                            </template>
                        </Column>
                        <Column
                            header="Orientador"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        >
                            <template #body="{ data }">
                                <Select
                                    v-model="forms[data.id].orientador"
                                    :options="
                                        advisorOptionsForLine(
                                            forms[data.id].linha_pesquisa,
                                        )
                                    "
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Selecione o orientador"
                                    class="w-full"
                                    :disabled="
                                        forms[data.id].linha_pesquisa === ''
                                    "
                                />
                            </template>
                        </Column>
                        <Column
                            header="Ações"
                            header-class="px-4 py-3 text-end w-36 whitespace-nowrap"
                            body-class="px-4 py-3 text-end w-36 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                <Button
                                    label="Salvar"
                                    icon="pi pi-check"
                                    size="small"
                                    :loading="forms[data.id].processing"
                                    :disabled="!canSave(data.id)"
                                    @click="saveRow(data.id)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
