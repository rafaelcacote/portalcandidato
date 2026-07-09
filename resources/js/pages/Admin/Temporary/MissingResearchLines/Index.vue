<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Heading from '@/components/Heading.vue';
import { home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as reportsIndex } from '@/routes/admin/reports';
import { show as missingResearchLinesProcessShow } from '@/routes/admin/temporary/missing-research-lines/processes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel administrativo', href: adminDashboard.url() },
            { title: 'Relatórios', href: reportsIndex().url },
            { title: 'Linhas pendentes (temporário)', href: '#' },
        ],
    },
});

defineProps<{
    processes: Array<{
        id: number;
        titulo: string;
        status: string;
        missing_research_lines_count: number;
    }>;
}>();

const statusSeverity: Record<
    string,
    'secondary' | 'success' | 'warn' | 'danger'
> = {
    rascunho: 'secondary',
    ativo: 'success',
    encerrado: 'warn',
};
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 md:py-4 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <Message severity="warn" :closable="false">
                Ferramenta temporária para preencher a linha de pesquisa de
                inscrições antigas. Remova este fluxo após concluir a
                atualização dos candidatos.
            </Message>

            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Linhas de pesquisa pendentes"
                    description="Processos com candidatos inscritos sem linha de pesquisa definida."
                    :icon="AlertTriangle"
                />
                <Link :href="reportsIndex().url">
                    <Button
                        label="Voltar aos relatórios"
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
                        :value="processes"
                        striped-rows
                        class="w-full"
                        table-style="min-width: 40rem; width: 100%; table-layout: fixed"
                    >
                        <template #empty>
                            <div
                                class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-check-circle text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhuma pendência encontrada
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Todos os candidatos inscritos já possuem
                                    linha de pesquisa definida.
                                </p>
                            </div>
                        </template>

                        <Column
                            field="titulo"
                            header="Processo"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        />
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
                            header="Sem linha"
                            header-class="px-4 py-3 w-28 whitespace-nowrap"
                            body-class="px-4 py-3 w-28 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                {{ data.missing_research_lines_count }}
                            </template>
                        </Column>
                        <Column
                            header="Ações"
                            header-class="px-4 py-3 text-end w-44 whitespace-nowrap"
                            body-class="px-4 py-3 text-end w-44 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                <div class="flex justify-end">
                                    <Link
                                        :href="
                                            missingResearchLinesProcessShow(
                                                data.id,
                                            ).url
                                        "
                                    >
                                        <Button
                                            label="Atualizar"
                                            icon="pi pi-pencil"
                                            size="small"
                                            outlined
                                        />
                                    </Link>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
