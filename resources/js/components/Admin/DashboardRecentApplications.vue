<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClipboardList } from 'lucide-vue-next';
import { index as processesIndex } from '@/routes/admin/processes';

defineProps<{
    applications: Array<{
        id: number;
        status: string;
        numero_protocolo: string | null;
        process_title: string;
        candidate_name: string;
        candidate_email: string;
        updated_at: string | null;
    }>;
}>();

function statusLabel(status: string): string {
    const map: Record<string, string> = {
        rascunho: 'Rascunho',
        inscrita: 'Confirmada',
        em_analise: 'Em análise',
        pendencia: 'Pendente',
        aprovada: 'Aprovada',
        reprovada: 'Reprovada',
        cancelada: 'Cancelada',
    };

    return map[status] ?? status;
}

function statusClasses(status: string): string {
    if (status === 'inscrita' || status === 'aprovada') {
        return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70';
    }

    if (status === 'em_analise') {
        return 'bg-sky-50 text-sky-700 ring-1 ring-sky-200/70';
    }

    if (status === 'pendencia') {
        return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/70';
    }

    if (status === 'reprovada' || status === 'cancelada') {
        return 'bg-red-50 text-red-600 ring-1 ring-red-200/70';
    }

    return 'bg-slate-100 text-slate-500 ring-1 ring-slate-200/70';
}

function initials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (!parts.length) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0]!.slice(0, 2).toUpperCase();
    }

    return `${parts[0]![0]}${parts[parts.length - 1]![0]}`.toUpperCase();
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) {
        return '—';
    }

    return new Date(dateStr).toLocaleString('pt-BR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const avatarPalettes = [
    'bg-teal-100 text-teal-700',
    'bg-violet-100 text-violet-700',
    'bg-sky-100 text-sky-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
];

function avatarClass(idx: number): string {
    return (
        avatarPalettes[idx % avatarPalettes.length] ??
        'bg-slate-100 text-slate-500'
    );
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
                    Últimas inscrições
                </h2>
            </div>
            <Link
                :href="processesIndex().url"
                class="text-xs font-semibold text-emerald-600 transition-colors hover:text-emerald-700"
            >
                Ver todas
            </Link>
        </div>

        <!-- List -->
        <ul
            v-if="applications.length > 0"
            class="flex-1 divide-y divide-slate-100"
        >
            <li
                v-for="(a, i) in applications"
                :key="a.id"
                class="flex items-center gap-3.5 px-5 py-3.5 transition-colors hover:bg-slate-50/70"
            >
                <!-- Avatar -->
                <div
                    :class="[
                        'flex size-9 shrink-0 items-center justify-center rounded-full text-[11px] font-bold',
                        avatarClass(i),
                    ]"
                >
                    {{ initials(a.candidate_name) }}
                </div>

                <!-- Info -->
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-800">
                        {{ a.candidate_name }}
                    </p>
                    <p class="mt-0.5 truncate text-[11px] text-slate-400">
                        {{ a.process_title }}
                    </p>
                </div>

                <!-- Right: date + badge -->
                <div class="flex shrink-0 flex-col items-end gap-1">
                    <span
                        :class="[
                            'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                            statusClasses(a.status),
                        ]"
                    >
                        {{ statusLabel(a.status) }}
                    </span>
                    <p class="text-[10px] text-slate-400">
                        {{ formatDateTime(a.updated_at) }}
                    </p>
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
                    Nenhuma inscrição ainda
                </p>
                <p class="mt-0.5 text-xs text-slate-400">
                    As inscrições dos candidatos aparecerão aqui.
                </p>
            </div>
        </div>
    </div>
</template>
