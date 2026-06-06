<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText, Plus } from 'lucide-vue-next';
import {
    create as processCreate,
    index as processesIndex,
    show as processShow,
} from '@/routes/admin/processes';

defineProps<{
    processes: Array<{
        id: number;
        titulo: string;
        status: string;
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
                    <FileText class="size-3.5" />
                </div>
                <h2 class="text-sm font-semibold text-slate-900">
                    Últimos processos
                </h2>
            </div>
            <Link
                :href="processesIndex().url"
                class="text-xs font-semibold text-emerald-600 transition-colors hover:text-emerald-700"
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
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400 transition-colors group-hover:bg-slate-200 group-hover:text-slate-600"
                >
                    <FileText class="size-4" />
                </div>

                <!-- Info -->
                <div class="min-w-0 flex-1">
                    <Link
                        :href="processShow({ selectionProcess: p.id }).url"
                        class="block truncate text-sm font-medium text-slate-800 transition-colors hover:text-emerald-700"
                    >
                        {{ p.titulo }}
                    </Link>
                    <p class="mt-0.5 text-[11px] text-slate-400">
                        {{ formatDate(p.inscricao_inicio_em) }}
                        <span class="mx-1 text-slate-300">—</span>
                        {{ formatDate(p.inscricao_fim_em) }}
                    </p>
                </div>

                <!-- Badge -->
                <span
                    :class="[
                        'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                        statusClasses(p.status),
                    ]"
                >
                    {{ statusLabel(p.status) }}
                </span>
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
                <FileText class="size-6" />
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-700">
                    Nenhum processo cadastrado
                </p>
                <p class="mt-0.5 text-xs text-slate-400">
                    Crie o primeiro processo seletivo.
                </p>
            </div>
            <Link :href="processCreate().url">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700"
                >
                    <Plus class="size-3.5" />
                    Criar processo
                </button>
            </Link>
        </div>
    </div>
</template>
