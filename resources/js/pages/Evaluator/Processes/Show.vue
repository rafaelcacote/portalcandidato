<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    ChevronLeft,
    Clock,
    FileSearch,
    Search,
    SlidersHorizontal,
    User,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { home } from '@/routes';
import { dashboard } from '@/routes/evaluator';
import { show as candidateShow } from '@/routes/evaluator/candidates';
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

const props = defineProps<{
    selectionProcess: {
        id: number;
        titulo: string;
        status: string;
        inscricao_inicio_em: string | null;
        inscricao_fim_em: string | null;
        criteria: Array<{ id: number; nome: string; pontuacao_max: number }>;
    };
    candidates: {
        data: Array<{
            id: number;
            status: string;
            numero_protocolo: string | null;
            user: { id: number; name: string; email: string; cpf?: string | null };
            evaluations: Array<{ id: number; resultado: string | null; pontuacao_total: number | null }>;
        }>;
        meta?: {
            current_page: number;
            last_page: number;
            total: number;
            links: Array<{ url: string | null; label: string; active: boolean }>;
        };
    };
    filters: {
        search: string;
        status: string;
    };
}>();

const statusFilters = [
    { value: 'all', label: 'Todos' },
    { value: 'enviada', label: 'Aguardando' },
    { value: 'em_analise', label: 'Em análise' },
    { value: 'pendencia', label: 'Doc. pendentes' },
    { value: 'aprovada', label: 'Aprovado' },
    { value: 'reprovada', label: 'Reprovado' },
];

const search = ref(props.filters.search ?? '');
const activeStatus = ref(props.filters.status ?? 'all');

let searchTimer: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        router.get(
            processShow({ selectionProcess: props.selectionProcess.id }).url,
            { search: value, status: activeStatus.value },
            { preserveState: true, replace: true },
        );
    }, 400);
});

function applyStatusFilter(status: string): void {
    activeStatus.value = status;
    router.get(
        processShow({ selectionProcess: props.selectionProcess.id }).url,
        { search: search.value, status },
        { preserveState: true, replace: true },
    );
}

function candidateStatusLabel(status: string): string {
    return (
        ({
            rascunho: 'Rascunho',
            enviada: 'Aguardando',
            em_analise: 'Em análise',
            pendencia: 'Doc. pendentes',
            aprovada: 'Aprovado',
            reprovada: 'Reprovado',
        } as Record<string, string>)[status] ?? status
    );
}

function candidateStatusClasses(status: string): string {
    const map: Record<string, string> = {
        enviada: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200/70',
        em_analise: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/70',
        pendencia: 'bg-orange-50 text-orange-700 ring-1 ring-orange-200/70',
        aprovada: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70',
        reprovada: 'bg-red-50 text-red-700 ring-1 ring-red-200/70',
        rascunho: 'bg-slate-100 text-slate-500 ring-1 ring-slate-200/70',
    };

    return map[status] ?? 'bg-slate-100 text-slate-500';
}

function hasBeenEvaluated(
    evaluations: Array<{ resultado: string | null }>,
): boolean {
    return evaluations.some((e) => e.resultado !== null);
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
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
        <Head :title="`Candidatos – ${selectionProcess.titulo}`" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <!-- Back + title -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <Link
                        :href="processesIndex().url"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 transition-colors hover:text-violet-600"
                    >
                        <ChevronLeft class="size-3.5" />
                        Voltar para processos
                    </Link>
                    <h1 class="mt-1.5 text-xl font-bold tracking-tight text-slate-900">
                        {{ selectionProcess.titulo }}
                    </h1>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Candidatos inscritos ·
                        Inscrições:
                        {{ formatDate(selectionProcess.inscricao_inicio_em) }}
                        <span class="mx-1 text-slate-300">–</span>
                        {{ formatDate(selectionProcess.inscricao_fim_em) }}
                    </p>
                </div>

                <!-- Total badge -->
                <div
                    v-if="candidates.meta"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-white px-4 py-2 shadow-sm ring-1 ring-slate-200"
                >
                    <span class="text-2xl font-bold tabular-nums text-slate-900">
                        {{ candidates.meta.total }}
                    </span>
                    <span class="text-xs font-medium text-slate-500">candidatos</span>
                </div>
            </div>

            <!-- Filters bar -->
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60 sm:flex-row sm:items-center">
                <!-- Search -->
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nome, CPF ou inscrição..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-4 text-sm text-slate-800 outline-none transition-colors placeholder:text-slate-400 focus:border-violet-400 focus:bg-white focus:ring-2 focus:ring-violet-400/20"
                    />
                </div>

                <!-- Status filter pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto">
                    <SlidersHorizontal class="size-3.5 shrink-0 text-slate-400" />
                    <button
                        v-for="f in statusFilters"
                        :key="f.value"
                        type="button"
                        class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition-all"
                        :class="
                            activeStatus === f.value
                                ? 'bg-violet-600 text-white shadow-sm'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        "
                        @click="applyStatusFilter(f.value)"
                    >
                        {{ f.label }}
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="candidates.data.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white py-20 text-center shadow-sm"
            >
                <div class="flex size-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <FileSearch class="size-7" />
                </div>
                <p class="mt-4 text-base font-semibold text-slate-700">
                    Nenhum candidato encontrado
                </p>
                <p class="mt-1 text-sm text-slate-400">
                    Tente outros filtros ou aguarde novas inscrições.
                </p>
            </div>

            <!-- Candidates list -->
            <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
                <ul class="divide-y divide-slate-100">
                    <li
                        v-for="candidate in candidates.data"
                        :key="candidate.id"
                        class="group flex items-center gap-4 px-5 py-4 transition-colors hover:bg-slate-50/70"
                    >
                        <!-- Avatar -->
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-100 to-indigo-100 text-sm font-bold text-violet-700">
                            {{ getInitials(candidate.user.name) }}
                        </div>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">
                                {{ candidate.user.name }}
                            </p>
                            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-slate-400">
                                <span>{{ candidate.user.email }}</span>
                                <span v-if="candidate.numero_protocolo" class="font-mono">
                                    #{{ candidate.numero_protocolo }}
                                </span>
                            </div>
                        </div>

                        <!-- Evaluation badge -->
                        <div class="flex shrink-0 flex-col items-end gap-1.5">
                            <span
                                :class="[
                                    'rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                                    candidateStatusClasses(candidate.status),
                                ]"
                            >
                                {{ candidateStatusLabel(candidate.status) }}
                            </span>
                            <span
                                v-if="hasBeenEvaluated(candidate.evaluations)"
                                class="inline-flex items-center gap-1 text-[10px] font-medium text-emerald-600"
                            >
                                <CheckCircle2 class="size-3" />
                                Avaliado
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-[10px] font-medium text-amber-600"
                            >
                                <Clock class="size-3" />
                                Pendente
                            </span>
                        </div>

                        <!-- CTA -->
                        <Link
                            :href="candidateShow({ application: candidate.id }).url"
                            class="group/link ml-2 inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-violet-50 px-3.5 py-2 text-xs font-semibold text-violet-700 ring-1 ring-violet-200/70 transition-all hover:bg-violet-600 hover:text-white hover:ring-violet-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400/60"
                        >
                            <User class="size-3.5" />
                            Avaliar
                            <ArrowRight class="size-3.5 transition-transform duration-150 group-hover/link:translate-x-0.5" />
                        </Link>
                    </li>
                </ul>
            </div>

            <!-- Pagination -->
            <div
                v-if="candidates.meta && candidates.meta.last_page > 1"
                class="flex items-center justify-center gap-1"
            >
                <template v-for="link in candidates.meta.links" :key="link.label">
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
