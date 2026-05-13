<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import EvaluatorAssignedProcessList from '@/components/Evaluator/EvaluatorAssignedProcessList.vue';
import EvaluatorDashboardHero from '@/components/Evaluator/EvaluatorDashboardHero.vue';
import EvaluatorDashboardStats from '@/components/Evaluator/EvaluatorDashboardStats.vue';
import { home } from '@/routes';
import { dashboard as evaluatorDashboard } from '@/routes/evaluator';
import type { Auth } from '@/types/auth';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel do avaliador', href: evaluatorDashboard.url() },
        ],
    },
});

const props = defineProps<{
    stats: {
        processes_total: number;
        candidates_total: number;
        pending_analysis: number;
        analysis_completed: number;
    };
    recent_processes: Array<{
        id: number;
        titulo: string;
        status: string;
        total_candidates: number;
        pending_candidates: number;
        analyzed_candidates: number;
        inscricao_inicio_em: string | null;
        inscricao_fim_em: string | null;
    }>;
}>();

const page = usePage<{ auth: Auth }>();

const displayName = computed(() => {
    const name = page.props.auth?.user?.name?.trim() ?? 'Avaliador';

    return name.split(/\s+/)[0] ?? name;
});

const highlightProcess = computed(() => {
    return props.recent_processes.find((p) => p.pending_candidates > 0) ?? null;
});
</script>

<template>
    <div class="min-h-0 flex-1 bg-background p-3 md:p-5">
        <Head title="Painel do avaliador" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <!-- Hero -->
            <EvaluatorDashboardHero
                :display-name="displayName"
                :processes-total="stats.processes_total"
                :pending-analysis="stats.pending_analysis"
                :highlight-process="highlightProcess"
            />

            <!-- KPI cards -->
            <EvaluatorDashboardStats :stats="stats" />

            <!-- Processos atribuídos -->
            <EvaluatorAssignedProcessList :processes="recent_processes" />
        </div>
    </div>
</template>
