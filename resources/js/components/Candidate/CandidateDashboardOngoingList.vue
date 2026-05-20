<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClipboardList } from 'lucide-vue-next';
import {
    index as applicationsIndex,
    show as applicationShow,
} from '@/routes/candidate/applications';

defineProps<{
    applications: Array<{
        id: number;
        status: string;
        process_title: string;
        numero_protocolo: string | null;
    }>;
}>();

function statusLabel(status: string): string {
    const labels: Record<string, string> = {
        rascunho: 'Rascunho',
        em_analise: 'Em análise',
    };

    return labels[status] ?? status;
}

function statusClasses(status: string): string {
    if (status === 'rascunho') {
        return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200/70';
    }

    return 'bg-sky-50 text-sky-700 ring-1 ring-sky-200/70';
}
</script>

<template>
    <div class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div class="flex items-center gap-2">
                <div class="flex size-7 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                    <ClipboardList class="size-3.5" />
                </div>
                <h2 class="text-sm font-semibold text-slate-900">Inscrições em andamento</h2>
            </div>
            <Link
                :href="applicationsIndex.url()"
                class="text-xs font-semibold text-teal-600 transition-colors hover:text-teal-700"
            >
                Ver todas
            </Link>
        </div>

        <ul v-if="applications.length" class="divide-y divide-slate-100">
            <li
                v-for="row in applications"
                :key="row.id"
                class="px-5 py-3.5 transition-colors hover:bg-slate-50/80"
            >
                <Link
                    :href="applicationShow.url({ application: row.id })"
                    class="group block"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900 group-hover:text-teal-700">
                                {{ row.process_title }}
                            </p>
                            <p v-if="row.numero_protocolo" class="mt-0.5 text-xs text-slate-400">
                                {{ row.numero_protocolo }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                            :class="statusClasses(row.status)"
                        >
                            {{ statusLabel(row.status) }}
                        </span>
                    </div>
                </Link>
            </li>
        </ul>

        <div
            v-else
            class="flex flex-col items-center justify-center px-6 py-14 text-center"
        >
            <div class="flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <ClipboardList class="size-6" />
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-700">Nenhuma inscrição em andamento</p>
            <p class="mt-1 max-w-xs text-xs text-slate-400">
                Quando você iniciar ou tiver inscrições em análise, elas aparecerão aqui.
            </p>
        </div>
    </div>
</template>
