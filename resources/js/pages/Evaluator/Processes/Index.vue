<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, ClipboardList, Clock, Users } from 'lucide-vue-next';
import { home } from '@/routes';
import { dashboard as evaluatorDashboard, dashboard } from '@/routes/evaluator';
import { index as processesIndex, show as processShow } from '@/routes/evaluator/processes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel do avaliador', href: dashboard.url() },
            { title: 'Processos', href: processesIndex().url },
        ],
    },
});

defineProps<{
    processes: {
        data: Array<{
            id: number;
            titulo: string;
            status: string;
            total_candidates: number;
            pending_candidates: number;
            analyzed_candidates: number;
            inscricao_inicio_em: string | null;
            inscricao_fim_em: string | null;
        }>;
        meta?: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: Array<{ url: string | null; label: string; active: boolean }>;
        };
    };
}>();

function statusLabel(status: string): string {
    return (
        ({
            rascunho: 'Rascunho',
            ativo: 'Ativo',
            encerrado: 'Encerrado',
        } as Record<string, string>)[status] ?? status
    );
}

function statusClasses(status: string): string {
    if (status === 'ativo') {
        return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70';
    }

    if (status === 'rascunho') {
        return 'bg-slate-100 text-slate-500 ring-1 ring-slate-200/70';
    }

    return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/70';
}

function progressPercent(analyzed: number, total: number): number {
    if (total === 0) {
        return 0;
    }

    return Math.round((analyzed / total) * 100);
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) {
        return '—';
    }

    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <div class="min-h-0 flex-1 bg-background p-3 md:p-5">
        <Head title="Processos atribuídos" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <!-- Page header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">
                        Processos atribuídos
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Processos seletivos nos quais você está designado como avaliador.
                    </p>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="processes.data.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white py-20 text-center shadow-sm"
            >
                <div class="flex size-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <ClipboardList class="size-7" />
                </div>
                <p class="mt-4 text-base font-semibold text-slate-700">
                    Nenhum processo atribuído
                </p>
                <p class="mt-1 text-sm text-slate-400">
                    Aguarde a atribuição de processos pelo administrador.
                </p>
            </div>

            <!-- Process cards grid -->
            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="process in processes.data"
                    :key="process.id"
                    class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:ring-slate-300/60"
                >
                    <!-- Card header -->
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                <ClipboardList class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-slate-900">
                                    {{ process.titulo }}
                                </h3>
                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    {{ formatDate(process.inscricao_inicio_em) }}
                                    <span class="mx-1">–</span>
                                    {{ formatDate(process.inscricao_fim_em) }}
                                </p>
                            </div>
                        </div>
                        <span
                            :class="[
                                'shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                                statusClasses(process.status),
                            ]"
                        >
                            {{ statusLabel(process.status) }}
                        </span>
                    </div>

                    <!-- Stats row -->
                    <div class="flex items-center gap-4 px-5 py-3">
                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                            <Users class="size-3.5 text-slate-400" />
                            <span class="font-medium text-slate-700">{{ process.total_candidates }}</span>
                            candidatos
                        </div>
                        <div
                            class="flex items-center gap-1.5 text-xs"
                            :class="process.pending_candidates > 0 ? 'text-amber-600' : 'text-emerald-600'"
                        >
                            <Clock v-if="process.pending_candidates > 0" class="size-3.5" />
                            <CheckCircle2 v-else class="size-3.5" />
                            <span class="font-semibold">{{ process.pending_candidates }}</span>
                            pendente{{ process.pending_candidates !== 1 ? 's' : '' }}
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400">
                            <CheckCircle2 class="size-3.5 text-emerald-500" />
                            <span class="font-medium text-slate-600">{{ process.analyzed_candidates }}</span>
                            avaliados
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="px-5 pb-3">
                        <div class="flex items-center justify-between text-[10px] font-medium text-slate-400">
                            <span>Progresso de análise</span>
                            <span>{{ progressPercent(process.analyzed_candidates, process.total_candidates) }}%</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-violet-500 to-indigo-500 transition-all duration-500"
                                :style="`width: ${progressPercent(process.analyzed_candidates, process.total_candidates)}%`"
                            />
                        </div>
                    </div>

                    <!-- CTA button -->
                    <div class="mt-auto border-t border-slate-100 px-5 py-3">
                        <Link
                            :href="processShow({ selectionProcess: process.id }).url"
                            class="group/btn inline-flex w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-violet-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400/60 focus-visible:ring-offset-1"
                        >
                            <Users class="size-4" />
                            Ver candidatos inscritos
                            <ArrowRight class="ml-auto size-4 transition-transform duration-200 group-hover/btn:translate-x-0.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="processes.meta && processes.meta.last_page > 1"
                class="flex items-center justify-center gap-1"
            >
                <template v-for="link in processes.meta.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="
                            link.active
                                ? 'bg-violet-600 text-white'
                                : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'
                        "
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs font-medium text-slate-300"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
