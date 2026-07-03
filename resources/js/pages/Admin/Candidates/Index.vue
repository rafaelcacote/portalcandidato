<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { UserCircle } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { cpfDigitsOnly, formatCpfDisplay } from '@/lib/brDocuments';
import { show as showCandidatePage } from '@/routes/admin/candidates';

type Candidate = {
    id: number;
    name: string;
    email: string;
    cpf?: string | null;
    telefone?: string | null;
    ativo: boolean;
    email_verified: boolean;
    profile_complete: boolean;
    applications_count: number;
    created_at: string | null;
};

const props = defineProps<{
    candidates: Candidate[];
}>();

const vTooltip = Tooltip;

const searchTerm = ref<string>('');
const selectedStatus = ref<boolean | null>(null);
const selectedProfile = ref<boolean | null>(null);
const selectedEnrollment = ref<'all' | 'with' | 'without'>('all');

const statusFilterOptions = [
    { label: 'Todos os status', value: null },
    { label: 'Ativo', value: true },
    { label: 'Inativo', value: false },
];

const profileFilterOptions = [
    { label: 'Todos os perfis', value: null },
    { label: 'Perfil completo', value: true },
    { label: 'Perfil incompleto', value: false },
];

const enrollmentFilterOptions = [
    { label: 'Todas as inscrições', value: 'all' },
    { label: 'Com inscrição', value: 'with' },
    { label: 'Sem inscrição', value: 'without' },
];

const filteredCandidates = computed(() => {
    const needle = searchTerm.value.trim().toLowerCase();
    const needleDigits = needle.replace(/\D/g, '');

    return props.candidates.filter((candidate) => {
        const cpfDigits = cpfDigitsOnly(candidate.cpf ?? '');
        const matchesCpfSearch =
            needleDigits.length >= 3 && cpfDigits.includes(needleDigits);

        const matchesSearch =
            candidate.name.toLowerCase().includes(needle) ||
            candidate.email.toLowerCase().includes(needle) ||
            matchesCpfSearch;

        const matchesStatus =
            selectedStatus.value !== null
                ? candidate.ativo === selectedStatus.value
                : true;

        const matchesProfile =
            selectedProfile.value !== null
                ? candidate.profile_complete === selectedProfile.value
                : true;

        const matchesEnrollment =
            selectedEnrollment.value === 'with'
                ? candidate.applications_count > 0
                : selectedEnrollment.value === 'without'
                  ? candidate.applications_count === 0
                  : true;

        return (
            matchesSearch &&
            matchesStatus &&
            matchesProfile &&
            matchesEnrollment
        );
    });
});

function candidateCpfSubtitle(cpf: string | null | undefined): string | null {
    if (cpfDigitsOnly(cpf ?? '').length !== 11) {
        return null;
    }

    return formatCpfDisplay(cpf);
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) {
        return '-';
    }

    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

const hasNoRecords = computed(() => props.candidates.length === 0);

function clearFilters(): void {
    searchTerm.value = '';
    selectedStatus.value = null;
    selectedProfile.value = null;
    selectedEnrollment.value = 'all';
}
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 md:py-4 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Candidatos"
                    description="Todos os candidatos cadastrados no sistema, com ou sem inscrição em processos seletivos."
                    :icon="UserCircle"
                />
            </div>

            <Card class="overflow-hidden rounded-xl shadow-md">
                <template #content>
                    <div
                        class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-[1fr_200px_200px_200px_auto]"
                    >
                        <InputText
                            v-model="searchTerm"
                            placeholder="Buscar por nome, e-mail ou CPF"
                        />
                        <Select
                            v-model="selectedStatus"
                            :options="statusFilterOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Status"
                        />
                        <Select
                            v-model="selectedProfile"
                            :options="profileFilterOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Perfil"
                        />
                        <Select
                            v-model="selectedEnrollment"
                            :options="enrollmentFilterOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Inscrições"
                        />
                        <Button
                            label="Limpar"
                            severity="secondary"
                            outlined
                            size="small"
                            @click="clearFilters"
                        />
                    </div>

                    <DataTable
                        :value="filteredCandidates"
                        striped-rows
                        class="w-full"
                        table-style="width: 100%; table-layout: fixed"
                    >
                        <template #empty>
                            <div
                                v-if="hasNoRecords"
                                class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-user text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum candidato cadastrado
                                </p>
                                <p
                                    class="max-w-md text-sm text-muted-foreground"
                                >
                                    Ainda não existem candidatos registrados no
                                    sistema.
                                </p>
                            </div>
                            <div
                                v-else
                                class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center"
                            >
                                <i
                                    class="pi pi-filter-slash text-3xl text-muted-foreground"
                                />
                                <p class="text-base font-medium">
                                    Nenhum resultado com estes filtros
                                </p>
                                <Button
                                    label="Limpar filtros"
                                    icon="pi pi-times"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    @click="clearFilters"
                                />
                            </div>
                        </template>

                        <Column
                            header="Nome"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0 align-top"
                        >
                            <template #body="{ data }">
                                <div
                                    class="flex min-w-0 flex-col gap-0.5 py-0.5"
                                >
                                    <span
                                        class="truncate text-sm leading-tight font-medium text-foreground"
                                    >
                                        {{ data.name }}
                                    </span>
                                    <span
                                        v-if="candidateCpfSubtitle(data.cpf)"
                                        class="truncate font-mono text-[11px] leading-snug tracking-wide text-muted-foreground/90"
                                    >
                                        {{ candidateCpfSubtitle(data.cpf) }}
                                    </span>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="email"
                            header="E-mail"
                            header-class="px-4 py-3 min-w-0"
                            body-class="px-4 py-3 min-w-0 align-top"
                        />
                        <Column
                            header="Telefone"
                            header-class="px-4 py-3 whitespace-nowrap"
                            body-class="px-4 py-3 whitespace-nowrap align-top"
                        >
                            <template #body="{ data }">
                                {{ data.telefone ?? '-' }}
                            </template>
                        </Column>
                        <Column
                            header="Inscrições"
                            header-class="px-4 py-3 text-center w-28 whitespace-nowrap"
                            body-class="px-4 py-3 text-center w-28 whitespace-nowrap align-top"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :value="String(data.applications_count)"
                                    :severity="
                                        data.applications_count > 0
                                            ? 'info'
                                            : 'secondary'
                                    "
                                />
                            </template>
                        </Column>
                        <Column
                            header="Perfil"
                            header-class="px-4 py-3 w-36 whitespace-nowrap"
                            body-class="px-4 py-3 w-36 whitespace-nowrap align-top"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :value="
                                        data.profile_complete
                                            ? 'Completo'
                                            : 'Incompleto'
                                    "
                                    :severity="
                                        data.profile_complete
                                            ? 'success'
                                            : 'warn'
                                    "
                                />
                            </template>
                        </Column>
                        <Column
                            header="Verificação"
                            header-class="px-4 py-3 w-32 whitespace-nowrap"
                            body-class="px-4 py-3 w-32 whitespace-nowrap align-top"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :value="
                                        data.email_verified
                                            ? 'Verificado'
                                            : 'Pendente'
                                    "
                                    :severity="
                                        data.email_verified
                                            ? 'success'
                                            : 'secondary'
                                    "
                                />
                            </template>
                        </Column>
                        <Column
                            header="Status"
                            header-class="px-4 py-3 w-28 whitespace-nowrap"
                            body-class="px-4 py-3 w-28 whitespace-nowrap align-top"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :value="data.ativo ? 'Ativo' : 'Inativo'"
                                    :severity="
                                        data.ativo ? 'success' : 'secondary'
                                    "
                                />
                            </template>
                        </Column>
                        <Column
                            header="Cadastro"
                            header-class="px-4 py-3 w-32 whitespace-nowrap"
                            body-class="px-4 py-3 w-32 whitespace-nowrap align-top"
                        >
                            <template #body="{ data }">
                                {{ formatDate(data.created_at) }}
                            </template>
                        </Column>
                        <Column
                            header="Ações"
                            header-class="px-4 py-3 text-end w-24 whitespace-nowrap"
                            body-class="px-4 py-3 text-end w-24 whitespace-nowrap align-top"
                        >
                            <template #body="{ data }">
                                <div class="flex justify-end">
                                    <Link
                                        :href="showCandidatePage(data.id).url"
                                        class="inline-flex"
                                    >
                                        <Button
                                            v-tooltip.left="
                                                'Visualizar dados cadastrais'
                                            "
                                            rounded
                                            text
                                            icon="pi pi-eye"
                                        />
                                    </Link>
                                </div>
                            </template>
                        </Column>

                        <template #footer>
                            <div
                                class="px-2 py-3 text-sm text-muted-foreground"
                            >
                                Exibindo {{ filteredCandidates.length }} de
                                {{ props.candidates.length }}
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
