<script setup lang="ts">
import { AlertTriangle, ClipboardList, MessageSquare } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    summary: {
        inscricoes_em_andamento: number;
        pendencias: number;
        mensagens_nao_lidas: number;
    };
}>();

const cards = computed(() => [
    {
        label: 'Inscrições em andamento',
        value: String(props.summary.inscricoes_em_andamento),
        sub: 'Rascunhos e em análise',
        iconComponent: ClipboardList,
        iconBg: 'bg-sky-100',
        iconColor: 'text-sky-700',
        glowColor: 'rgba(14,165,233,0.08)',
    },
    {
        label: 'Pendências',
        value: String(props.summary.pendencias),
        sub: 'Inscrições e documentos',
        iconComponent: AlertTriangle,
        iconBg: 'bg-amber-100',
        iconColor: 'text-amber-700',
        glowColor: 'rgba(245,158,11,0.08)',
        highlight: props.summary.pendencias > 0,
    },
    {
        label: 'Mensagens',
        value: String(props.summary.mensagens_nao_lidas),
        sub: 'Notificações não lidas',
        iconComponent: MessageSquare,
        iconBg: 'bg-violet-100',
        iconColor: 'text-violet-700',
        glowColor: 'rgba(139,92,246,0.08)',
        highlight: props.summary.mensagens_nao_lidas > 0,
    },
]);
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
            v-for="card in cards"
            :key="card.label"
            class="group relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
            :class="
                card.highlight
                    ? 'ring-amber-300/60 hover:ring-amber-400/60'
                    : 'ring-slate-200/60 hover:ring-slate-300/60'
            "
        >
            <div class="flex items-start justify-between gap-3">
                <div
                    :class="[
                        'flex size-11 shrink-0 items-center justify-center rounded-xl',
                        card.iconBg,
                        card.iconColor,
                    ]"
                >
                    <component :is="card.iconComponent" class="size-5" />
                </div>
                <span
                    v-if="card.highlight"
                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-600 ring-1 ring-amber-200"
                >
                    <span class="size-1.5 animate-pulse rounded-full bg-amber-500" />
                    Atenção
                </span>
            </div>

            <div class="mt-4">
                <p class="text-xs font-semibold text-slate-500">{{ card.label }}</p>
                <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight text-slate-900">
                    {{ card.value }}
                </p>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ card.sub }}</p>
            </div>

            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 h-24 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                :style="`background: linear-gradient(to top, ${card.glowColor}, transparent)`"
            />
        </div>
    </div>
</template>
