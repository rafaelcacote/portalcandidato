<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, ClipboardList, Users } from 'lucide-vue-next';
import {
    index as processesIndex,
    show as processShow,
} from '@/routes/evaluator/processes';

defineProps<{
    processes: Array<{
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

function statusLabel(status: string): string {
    return (
        (
            {
                rascunho: 'Rascunho',
                ativo: 'Ativo',
                encerrado: 'Encerrado',
            } as Record<string, string>
        )[status] ?? status
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
</script>

<template>
    <div
        class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex size-7 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
                >
                    <ClipboardList class="size-3.5" />
                </div>
                <h2 class="text-sm font-semibold text-slate-900">
                    Processos atribuídos
                </h2>
            </div>
            <Link
                :href="processesIndex().url"
                class="text-xs font-semibold text-violet-600 transition-colors hover:text-violet-700"
            >
                Ver todos
            </Link>
        </div>

        <!-- List -->
        <ul
            v-if="processes.length > 0"
            class="flex-1 divide-y divide-slate-100"
        >
            <li
                v-for="p in processes"
                :key="p.id"
                class="group flex items-center gap-3.5 px-5 py-3.5 transition-colors hover:bg-slate-50/70"
            >
                <!-- Icon -->
                <div
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-400 transition-colors group-hover:bg-violet-100 group-hover:text-violet-600"
                >
                    <ClipboardList class="size-4" />
                </div>

                <!-- Info -->
                <div class="min-w-0 flex-1">
                    <Link
                        :href="processShow({ selectionProcess: p.id }).url"
                        class="block truncate text-sm font-medium text-slate-800 transition-colors hover:text-violet-700"
                    >
                        {{ p.titulo }}
                    </Link>
                    <div class="mt-0.5 flex items-center gap-3">
                        <span
                            class="inline-flex items-center gap-1 text-[11px] text-slate-400"
                        >
                            <Users class="size-3" />
                            {{ p.total_candidates }} candidatos
                        </span>
                        <span
                            v-if="p.pending_candidates > 0"
                            class="text-[11px] font-medium text-amber-600"
                        >
                            {{ p.pending_candidates }}
                            {{
                                p.pending_candidates === 1
                                    ? 'avaliação pendente'
                                    : 'avaliações pendentes'
                            }}
                        </span>
                        <span
                            v-else
                            class="text-[11px] font-medium text-emerald-600"
                        >
                            Em dia
                        </span>
                    </div>
                </div>

                <!-- Badge + arrow -->
                <div class="flex shrink-0 items-center gap-2">
                    <span
                        :class="[
                            'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                            statusClasses(p.status),
                        ]"
                    >
                        {{ statusLabel(p.status) }}
                    </span>
                    <ArrowRight
                        class="size-3.5 text-slate-300 opacity-0 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-violet-500 group-hover:opacity-100"
                    />
                </div>
            </li>
        </ul>

        <!-- Empty state -->
        <div
            v-else
            class="flex flex-1 flex-col items-center justify-center gap-3 px-6 py-14 text-center"
        >
            <div
                class="flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
            >
                <ClipboardList class="size-6" />
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-700">
                    Nenhum processo atribuído
                </p>
                <p class="mt-0.5 text-xs text-slate-400">
                    Aguarde a atribuição de processos.
                </p>
            </div>
        </div>
    </div>
</template>
