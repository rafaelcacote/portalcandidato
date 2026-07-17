<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ClipboardCheck } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as reportsIndex } from '@/routes/admin/reports';
import {
    print as reportProcessPrint,
    show as reportProcessShow,
} from '@/routes/admin/reports/processes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel administrativo', href: adminDashboard.url() },
            { title: 'Relatórios', href: reportsIndex().url },
            { title: 'Candidatos inscritos', href: '#' },
        ],
    },
});

type FilterOption = {
    value: string;
    label: string;
};

const props = defineProps<{
    selectionProcess: {
        id: number;
        titulo: string;
        status: string;
    };
    candidates: {
        data: Array<{
            id: number;
            numero_protocolo: string | null;
            nome_completo: string | null;
            linha_pesquisa_label: string | null;
            cpf_mascarado: string | null;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search: string;
        pcd: string;
        vinculo: string;
        linha_pesquisa: string;
        orientador: string;
        status: string;
    };
    filterOptions: {
        pcd: FilterOption[];
        vinculo: FilterOption[];
        status: FilterOption[];
        researchLines: {
            lines: FilterOption[];
            advisors: Record<string, string[]>;
        };
    };
}>();

const searchTerm = ref(props.filters.search);
const pcdFilter = ref(props.filters.pcd || 'all');
const vinculoFilter = ref(props.filters.vinculo || 'all');
const linhaFilter = ref(props.filters.linha_pesquisa || '');
const orientadorFilter = ref(props.filters.orientador || '');
const statusFilter = ref(props.filters.status || 'all');

const lineOptions = computed(() => [
    { value: '', label: 'Todas' },
    ...props.filterOptions.researchLines.lines,
]);

const advisorOptions = computed(() => {
    const advisors =
        linhaFilter.value === ''
            ? Object.values(props.filterOptions.researchLines.advisors).flat()
            : (props.filterOptions.researchLines.advisors[linhaFilter.value] ??
              []);

    const uniqueAdvisors = [...new Set(advisors)];

    return [
        { value: '', label: 'Todos' },
        ...uniqueAdvisors.map((name) => ({
            value: name,
            label: name,
        })),
    ];
});

watch(linhaFilter, () => {
    const validAdvisors = advisorOptions.value.map((option) => option.value);

    if (!validAdvisors.includes(orientadorFilter.value)) {
        orientadorFilter.value = '';
    }
});

watch(
    () => props.filters,
    (filters) => {
        searchTerm.value = filters.search;
        pcdFilter.value = filters.pcd || 'all';
        vinculoFilter.value = filters.vinculo || 'all';
        linhaFilter.value = filters.linha_pesquisa || '';
        orientadorFilter.value = filters.orientador || '';
        statusFilter.value = filters.status || 'all';
    },
);

const filterQuery = (): Record<string, string | undefined> => ({
    search: searchTerm.value || undefined,
    pcd: pcdFilter.value !== 'all' ? pcdFilter.value : undefined,
    vinculo: vinculoFilter.value !== 'all' ? vinculoFilter.value : undefined,
    linha_pesquisa: linhaFilter.value || undefined,
    orientador: orientadorFilter.value || undefined,
    status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
});

const applyFilters = (): void => {
    router.get(reportProcessShow(props.selectionProcess.id).url, filterQuery(), {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = (): void => {
    searchTerm.value = '';
    pcdFilter.value = 'all';
    vinculoFilter.value = 'all';
    linhaFilter.value = '';
    orientadorFilter.value = '';
    statusFilter.value = 'all';

    router.get(
        reportProcessShow(props.selectionProcess.id).url,
        {},
        { preserveState: true, replace: true },
    );
};

const openPrint = (): void => {
    const url = reportProcessPrint(props.selectionProcess.id).url;
    const params = new URLSearchParams();

    Object.entries(filterQuery()).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    const query = params.toString();

    window.open(
        query ? `${url}?${query}` : url,
        '_blank',
        'noopener,noreferrer',
    );
};
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 md:py-4 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div
                class="flex flex-col gap-4 py-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <Heading
                    title="Candidatos inscritos"
                    :description="selectionProcess.titulo"
                    :icon="ClipboardCheck"
                />
                <div class="flex shrink-0 flex-wrap gap-2">
                    <Link :href="reportsIndex().url">
                        <Button
                            label="Voltar"
                            icon="pi pi-arrow-left"
                            severity="secondary"
                            outlined
                            size="small"
                        />
                    </Link>
                    <Button
                        label="Imprimir listagem"
                        icon="pi pi-print"
                        size="small"
                        @click="openPrint"
                    />
                </div>
            </div>

            <Card class="overflow-hidden rounded-xl shadow-md">
                <template #content>
                    <div class="mb-5 flex flex-col gap-3">
                        <div
                            class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
                        >
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium"
                                    >Busca</span
                                >
                                <InputText
                                    v-model="searchTerm"
                                    placeholder="Nome ou código de inscrição"
                                    class="w-full"
                                    @keyup.enter="applyFilters"
                                />
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium">PcD</span>
                                <Select
                                    v-model="pcdFilter"
                                    :options="filterOptions.pcd"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Selecione"
                                    class="w-full"
                                />
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium"
                                    >Vínculo empregatício</span
                                >
                                <Select
                                    v-model="vinculoFilter"
                                    :options="filterOptions.vinculo"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Selecione"
                                    class="w-full"
                                />
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium"
                                    >Linha de pesquisa</span
                                >
                                <Select
                                    v-model="linhaFilter"
                                    :options="lineOptions"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Selecione"
                                    class="w-full"
                                />
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium"
                                    >Orientador</span
                                >
                                <Select
                                    v-model="orientadorFilter"
                                    :options="advisorOptions"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Selecione"
                                    class="w-full"
                                />
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium"
                                    >Status da inscrição</span
                                >
                                <Select
                                    v-model="statusFilter"
                                    :options="filterOptions.status"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Selecione"
                                    class="w-full"
                                />
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                label="Filtrar"
                                icon="pi pi-filter"
                                size="small"
                                @click="applyFilters"
                            />
                            <Button
                                label="Limpar"
                                severity="secondary"
                                outlined
                                size="small"
                                @click="clearFilters"
                            />
                        </div>
                    </div>

                    <DataTable
                        :value="candidates.data"
                        striped-rows
                        class="w-full"
                        table-style="min-width: 40rem; width: 100%; table-layout: fixed"
                    >
                        <template #empty>
                            <div
                                class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-users text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum candidato encontrado
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Ajuste os filtros ou verifique se este
                                    processo possui candidatos com inscrição
                                    finalizada.
                                </p>
                            </div>
                        </template>

                        <Column
                            field="numero_protocolo"
                            header="Cod. inscrição"
                            header-class="px-4 py-3 w-40 whitespace-nowrap"
                            body-class="px-4 py-3 w-40 whitespace-nowrap"
                        />
                        <Column
                            field="nome_completo"
                            header="Nome completo"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        />
                        <Column
                            field="linha_pesquisa_label"
                            header="Linha"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
                        />
                        <Column
                            field="cpf_mascarado"
                            header="CPF"
                            header-class="px-4 py-3 w-40 whitespace-nowrap"
                            body-class="px-4 py-3 w-40 whitespace-nowrap font-mono"
                        />

                        <template #footer>
                            <div
                                class="px-2 py-3 text-sm text-muted-foreground"
                            >
                                Total nesta página: {{ candidates.data.length }}
                            </div>
                        </template>
                    </DataTable>

                    <div
                        v-if="candidates.links.length > 3"
                        class="mt-4 flex flex-wrap gap-2"
                    >
                        <Link
                            v-for="(link, index) in candidates.links"
                            :key="index"
                            :href="link.url ?? '#'"
                            class="rounded-md border px-3 py-1.5 text-sm transition-colors"
                            :class="[
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border text-muted-foreground hover:bg-muted',
                                { 'pointer-events-none opacity-50': !link.url },
                            ]"
                            preserve-scroll
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
