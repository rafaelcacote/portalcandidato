<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ClipboardList } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import CandidateAvatar from '@/components/Evaluator/CandidateAvatar.vue';
import Heading from '@/components/Heading.vue';
import { formatDateTimeBR } from '@/lib/utils';
import { home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as applicationsIndex } from '@/routes/admin/applications';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel administrativo', href: adminDashboard.url() },
            { title: 'Inscrições', href: applicationsIndex().url },
        ],
    },
});

const props = defineProps<{
    applications: {
        data: Array<{
            id: number;
            status: string;
            numero_protocolo: string | null;
            finalizada_em: string | null;
            created_at: string | null;
            updated_at: string | null;
            selection_process: {
                id: number;
                titulo: string;
                status: string;
            } | null;
            candidate: {
                id: number;
                name: string;
                email: string;
                photo_url?: string | null;
            } | null;
        }>;
    };
}>();

const statusSeverity: Record<
    string,
    'secondary' | 'success' | 'warn' | 'danger'
> = {
    rascunho: 'secondary',
    inscrita: 'success',
    em_analise: 'warn',
    pendencia: 'warn',
    aprovada: 'success',
    reprovada: 'danger',
    cancelada: 'secondary',
};

const statusLabel: Record<string, string> = {
    rascunho: 'Rascunho',
    inscrita: 'Confirmada',
    em_analise: 'Em análise',
    pendencia: 'Pendente',
    aprovada: 'Aprovada',
    reprovada: 'Reprovada',
    cancelada: 'Cancelada',
};

const statusOptions = [
    { label: 'Todos status', value: null },
    { label: 'Rascunho', value: 'rascunho' },
    { label: 'Confirmada', value: 'inscrita' },
    { label: 'Em análise', value: 'em_analise' },
    { label: 'Pendente', value: 'pendencia' },
    { label: 'Aprovada', value: 'aprovada' },
    { label: 'Reprovada', value: 'reprovada' },
    { label: 'Cancelada', value: 'cancelada' },
];

const searchTerm = ref<string>('');
const selectedStatus = ref<string | null>(null);

const filteredApplications = computed(() => {
    const needle = searchTerm.value.trim().toLowerCase();

    return props.applications.data.filter((application) => {
        const candidateName =
            application.candidate?.name?.toLowerCase() ?? '';
        const candidateEmail =
            application.candidate?.email?.toLowerCase() ?? '';
        const processTitle =
            application.selection_process?.titulo?.toLowerCase() ?? '';
        const protocol =
            application.numero_protocolo?.toLowerCase() ?? '';

        const matchesSearch =
            !needle ||
            candidateName.includes(needle) ||
            candidateEmail.includes(needle) ||
            processTitle.includes(needle) ||
            protocol.includes(needle);

        const matchesStatus = selectedStatus.value
            ? application.status === selectedStatus.value
            : true;

        return matchesSearch && matchesStatus;
    });
});

const hasNoRecords = computed(() => props.applications.data.length === 0);
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 md:py-4 lg:px-10">
        <Head title="Inscrições" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Inscrições"
                    description="Candidatos inscritos nos processos seletivos da plataforma."
                    :icon="ClipboardList"
                />
            </div>

            <Card class="overflow-hidden rounded-xl shadow-md">
                <template #content>
                    <div
                        class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-[1fr_220px_auto]"
                    >
                        <InputText
                            v-model="searchTerm"
                            placeholder="Buscar por candidato, processo ou protocolo"
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
                        :value="filteredApplications"
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
                                    {{
                                        hasNoRecords
                                            ? 'Nenhuma inscrição registrada'
                                            : 'Nenhuma inscrição encontrada'
                                    }}
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    {{
                                        hasNoRecords
                                            ? 'As inscrições dos candidatos aparecerão aqui quando forem criadas.'
                                            : 'Ajuste os filtros para localizar a inscrição desejada.'
                                    }}
                                </p>
                            </div>
                        </template>

                        <Column
                            field="id"
                            header="ID"
                            header-class="px-4 py-3 w-16 whitespace-nowrap"
                            body-class="px-4 py-3 w-16 whitespace-nowrap"
                        />
                        <Column
                            header="Candidato"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0 align-top"
                        >
                            <template #body="{ data }">
                                <div class="flex min-w-0 items-center gap-3">
                                    <CandidateAvatar
                                        :name="data.candidate?.name ?? '—'"
                                        :photo-url="data.candidate?.photo_url"
                                        size="sm"
                                    />
                                    <div class="flex min-w-0 flex-col gap-0.5">
                                        <span
                                            class="truncate text-sm font-medium text-foreground"
                                        >
                                            {{ data.candidate?.name ?? '—' }}
                                        </span>
                                        <span
                                            class="truncate text-[11px] text-muted-foreground"
                                        >
                                            {{ data.candidate?.email ?? '—' }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            header="Processo"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0 align-top"
                        >
                            <template #body="{ data }">
                                {{ data.selection_process?.titulo ?? '—' }}
                            </template>
                        </Column>
                        <Column
                            header="Protocolo"
                            header-class="px-4 py-3 whitespace-nowrap"
                            body-class="px-4 py-3 whitespace-nowrap align-top"
                        >
                            <template #body="{ data }">
                                {{ data.numero_protocolo ?? '—' }}
                            </template>
                        </Column>
                        <Column
                            header="Status"
                            header-class="px-4 py-3 w-36 whitespace-nowrap"
                            body-class="px-4 py-3 w-36 whitespace-nowrap"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :value="
                                        statusLabel[data.status] ?? data.status
                                    "
                                    :severity="
                                        statusSeverity[data.status] ??
                                        'secondary'
                                    "
                                />
                            </template>
                        </Column>
                        <Column
                            header="Atualizada em"
                            header-class="px-4 py-3 whitespace-nowrap"
                            body-class="px-4 py-3 whitespace-nowrap align-top"
                        >
                            <template #body="{ data }">
                                {{ formatDateTimeBR(data.updated_at) }}
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
