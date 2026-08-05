<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Select from 'primevue/select';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    contacts as reportsContacts,
    index as reportsIndex,
} from '@/routes/admin/reports';
import { print as reportsContactsPrint } from '@/routes/admin/reports/contacts';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel administrativo', href: adminDashboard.url() },
            { title: 'Relatórios', href: reportsIndex().url },
            { title: 'Contatos dos candidatos', href: '#' },
        ],
    },
});

type FilterOption = {
    value: string | number;
    label: string;
};

const props = defineProps<{
    candidates: {
        data: Array<{
            id: number;
            numero_protocolo: string | null;
            nome_completo: string | null;
            cpf_mascarado: string | null;
            email: string | null;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        selection_process_id: number | null;
    };
    filterOptions: {
        processes: FilterOption[];
    };
}>();

const processFilter = ref<number | null>(props.filters.selection_process_id);

const processOptions = computed(() => [
    { value: null, label: 'Todos' },
    ...props.filterOptions.processes,
]);

watch(
    () => props.filters,
    (filters) => {
        processFilter.value = filters.selection_process_id;
    },
);

const filterQuery = (): Record<string, string | number | undefined> => ({
    selection_process_id: processFilter.value ?? undefined,
});

const applyFilters = (): void => {
    router.get(reportsContacts().url, filterQuery(), {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = (): void => {
    processFilter.value = null;

    router.get(reportsContacts().url, {}, { preserveState: true, replace: true });
};

const openPrint = (): void => {
    const url = reportsContactsPrint().url;
    const params = new URLSearchParams();

    Object.entries(filterQuery()).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            params.set(key, String(value));
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
                    title="Contatos dos candidatos"
                    description="Listagem de candidatos com avaliação concluída, com código, nome, CPF e e-mail."
                    :icon="Mail"
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
                        label="Gerar PDF"
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
                                    >Processo seletivo</span
                                >
                                <Select
                                    v-model="processFilter"
                                    :options="processOptions"
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
                        table-style="min-width: 48rem; width: 100%; table-layout: fixed"
                    >
                        <template #empty>
                            <div
                                class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-envelope text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum candidato avaliado encontrado
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Ajuste o filtro de processo seletivo ou
                                    verifique se existem avaliações concluídas.
                                </p>
                            </div>
                        </template>

                        <Column
                            field="numero_protocolo"
                            header="Código"
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
                            field="cpf_mascarado"
                            header="CPF"
                            header-class="px-4 py-3 w-40 whitespace-nowrap"
                            body-class="px-4 py-3 w-40 whitespace-nowrap font-mono"
                        />
                        <Column
                            field="email"
                            header="E-mail"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0"
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
