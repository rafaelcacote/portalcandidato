<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ClipboardCheck } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import { ref } from 'vue';
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
    };
}>();

const searchTerm = ref(props.filters.search);

const applySearch = (): void => {
    router.get(
        reportProcessShow(props.selectionProcess.id).url,
        { search: searchTerm.value || undefined },
        { preserveState: true, replace: true },
    );
};

const clearSearch = (): void => {
    searchTerm.value = '';
    applySearch();
};

const openPrint = (): void => {
    window.open(
        reportProcessPrint(props.selectionProcess.id).url,
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
                    <div
                        class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto_auto]"
                    >
                        <InputText
                            v-model="searchTerm"
                            placeholder="Buscar por nome ou código de inscrição"
                            @keyup.enter="applySearch"
                        />
                        <Button
                            label="Buscar"
                            icon="pi pi-search"
                            size="small"
                            @click="applySearch"
                        />
                        <Button
                            label="Limpar"
                            severity="secondary"
                            outlined
                            size="small"
                            @click="clearSearch"
                        />
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
                                    Nenhum candidato inscrito
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Este processo ainda não possui candidatos com
                                    inscrição finalizada.
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
