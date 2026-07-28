<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClipboardCheck } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { formatDateTimeBR } from '@/lib/utils';
import { home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    evaluated as reportsEvaluated,
    index as reportsIndex,
} from '@/routes/admin/reports';
import { index as missingResearchLinesIndex } from '@/routes/admin/temporary/missing-research-lines';
import { show as reportProcessShow } from '@/routes/admin/reports/processes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel administrativo', href: adminDashboard.url() },
            { title: 'Relatórios', href: reportsIndex().url },
        ],
    },
});

const props = defineProps<{
    processes: Array<{
        id: number;
        titulo: string;
        status: string;
        inscricao_inicio_em?: string | null;
        inscricao_fim_em?: string | null;
        enrolled_candidates_count: number;
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

const searchTerm = ref<string>('');
const selectedStatus = ref<string | null>(null);

const statusOptions = [
    { label: 'Todos status', value: null },
    { label: 'Rascunho', value: 'rascunho' },
    { label: 'Ativo', value: 'ativo' },
    { label: 'Encerrado', value: 'encerrado' },
];

const filteredProcesses = computed(() => {
    return props.processes.filter((process) => {
        const matchesSearch = process.titulo
            .toLowerCase()
            .includes(searchTerm.value.toLowerCase());
        const matchesStatus = selectedStatus.value
            ? process.status === selectedStatus.value
            : true;

        return matchesSearch && matchesStatus;
    });
});
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 md:py-4 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Relatórios"
                    description="Consulte candidatos inscritos por processo ou candidatos já avaliados com nota."
                    :icon="ClipboardCheck"
                />
                <div class="flex shrink-0 flex-wrap gap-2">
                    <Link :href="reportsEvaluated().url">
                        <Button
                            label="Candidatos avaliados"
                            icon="pi pi-check-square"
                            size="small"
                        />
                    </Link>
                    <Link :href="missingResearchLinesIndex().url">
                        <Button
                            label="Linhas pendentes (temp.)"
                            icon="pi pi-exclamation-triangle"
                            severity="warn"
                            outlined
                            size="small"
                        />
                    </Link>
                </div>
            </div>

            <Card class="overflow-hidden rounded-xl shadow-md">
                <template #content>
                    <div
                        class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-[1fr_220px_auto]"
                    >
                        <InputText
                            v-model="searchTerm"
                            placeholder="Buscar por título do processo"
                        />
                        <Select
                            v-model="selectedStatus"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Todos status"
                        />
                        <Button
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
                        :value="filteredProcesses"
                        striped-rows
                        class="w-full"
                        table-style="min-width: 50rem; width: 100%; table-layout: fixed"
                    >
                        <template #empty>
                            <div
                                class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-inbox text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum processo cadastrado
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Ainda não existem processos seletivos para
                                    gerar relatórios.
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
                            header="Início inscrição"
                            header-class="px-4 py-3 whitespace-nowrap"
                            body-class="px-4 py-3 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                {{ formatDateTimeBR(data.inscricao_inicio_em) }}
                            </template>
                        </Column>
                        <Column
                            header="Fim inscrição"
                            header-class="px-4 py-3 whitespace-nowrap"
                            body-class="px-4 py-3 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                {{ formatDateTimeBR(data.inscricao_fim_em) }}
                            </template>
                        </Column>
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
                            header="Inscritos"
                            header-class="px-4 py-3 w-28 whitespace-nowrap"
                            body-class="px-4 py-3 w-28 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                {{ data.enrolled_candidates_count }}
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
                                            reportProcessShow(data.id).url
                                        "
                                    >
                                        <Button
                                            label="Candidatos"
                                            icon="pi pi-users"
                                            size="small"
                                            outlined
                                        />
                                    </Link>
                                </div>
                            </template>
                        </Column>

                        <template #footer>
                            <div
                                class="px-2 py-3 text-sm text-muted-foreground"
                            >
                                Total: {{ filteredProcesses.length }}
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
