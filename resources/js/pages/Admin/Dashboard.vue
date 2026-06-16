<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Select from 'primevue/select';
import { computed, ref } from 'vue';
import AdminApplicationsTrendChart from '@/components/Admin/AdminApplicationsTrendChart.vue';
import DashboardHero from '@/components/Admin/DashboardHero.vue';
import DashboardQuickActions from '@/components/Admin/DashboardQuickActions.vue';
import DashboardRecentApplications from '@/components/Admin/DashboardRecentApplications.vue';
import DashboardRecentProcesses from '@/components/Admin/DashboardRecentProcesses.vue';
import DashboardStats from '@/components/Admin/DashboardStats.vue';
import { home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import type { Auth } from '@/types/auth';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel administrativo', href: adminDashboard.url() },
        ],
    },
});

defineProps<{
    stats: {
        processes_total: number;
        processes_rascunho: number;
        processes_ativo: number;
        processes_encerrado: number;
        applications_total: number;
        applications_rascunho: number;
        applications_em_fluxo: number;
        applications_aprovada: number;
        evaluators_total: number;
        conversion_percent: number;
    };
    applications_trend: number[];
    highlight_process: {
        id: number;
        titulo: string;
        inscricao_inicio_em: string | null;
        inscricao_fim_em: string | null;
    } | null;
    recent_processes: Array<{
        id: number;
        titulo: string;
        status: string;
        inscricao_inicio_em: string | null;
        inscricao_fim_em: string | null;
    }>;
    recent_applications: Array<{
        id: number;
        status: string;
        numero_protocolo: string | null;
        process_title: string;
        candidate_name: string;
        candidate_email: string;
        candidate_photo_url?: string | null;
        updated_at: string | null;
    }>;
}>();

const page = usePage<{ auth: Auth }>();

const displayName = computed(() => {
    const name = page.props.auth?.user?.name?.trim() ?? 'Administrador';

    return name.split(/\s+/)[0] ?? name;
});

const chartPeriod = ref({ label: 'Últimos 30 dias', value: 30 });
const chartPeriodOptions = [{ label: 'Últimos 30 dias', value: 30 }];
</script>

<template>
    <div class="min-h-0 flex-1 bg-background p-3 md:p-5">
        <Head title="Painel administrativo" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <!-- ── Hero ─────────────────────────────────────────────────── -->
            <DashboardHero
                :display-name="displayName"
                :processes-ativo="stats.processes_ativo"
                :applications-total="stats.applications_total"
                :highlight-process="highlight_process"
            />

            <!-- ── KPI cards ─────────────────────────────────────────────── -->
            <DashboardStats :stats="stats" :trend="applications_trend" />

            <!-- ── Quick actions + trend chart ─────────────────────────── -->
            <div class="grid gap-5 lg:grid-cols-12">
                <!-- Quick actions (5 cols) -->
                <div class="lg:col-span-5">
                    <DashboardQuickActions />
                </div>

                <!-- Trend chart (7 cols) -->
                <div
                    class="flex flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 lg:col-span-7"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                Inscrições ao longo do tempo
                            </h2>
                            <p class="mt-0.5 text-[11px] text-slate-400">
                                Volume diário de novas inscrições registradas
                            </p>
                        </div>
                        <Select
                            v-model="chartPeriod"
                            :options="chartPeriodOptions"
                            option-label="label"
                            class="shrink-0 !rounded-xl !border-slate-200 !text-xs"
                        />
                    </div>
                    <div class="mt-4 flex-1">
                        <AdminApplicationsTrendChart
                            :values="applications_trend"
                        />
                    </div>
                </div>
            </div>

            <!-- ── Recent lists ──────────────────────────────────────────── -->
            <div class="grid gap-5 lg:grid-cols-2">
                <DashboardRecentProcesses :processes="recent_processes" />
                <DashboardRecentApplications
                    :applications="recent_applications"
                />
            </div>
        </div>
    </div>
</template>
