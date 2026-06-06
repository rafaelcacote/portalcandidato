<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
    /** When true, rascunho is shown as "Em andamento" if any milestone beyond draft exists */
    hasStarted?: boolean;
}>();

type Tone = 'neutral' | 'info' | 'success' | 'warning' | 'danger';

const resolved = computed(() => {
    const s = props.status;

    if (s === 'rascunho') {
        if (props.hasStarted) {
            return { label: 'Em andamento', tone: 'info' as Tone };
        }

        return { label: 'Rascunho', tone: 'neutral' as Tone };
    }

    if (s === 'pendencia') {
        return { label: 'Pendente', tone: 'warning' as Tone };
    }

    if (s === 'inscrita') {
        return { label: 'Enviado', tone: 'success' as Tone };
    }

    if (s === 'em_analise') {
        return { label: 'Em análise', tone: 'info' as Tone };
    }

    if (s === 'aprovada') {
        return { label: 'Aprovado', tone: 'success' as Tone };
    }

    if (s === 'reprovada') {
        return { label: 'Reprovado', tone: 'danger' as Tone };
    }

    if (s === 'cancelada') {
        return { label: 'Cancelada', tone: 'neutral' as Tone };
    }

    return { label: s, tone: 'neutral' as Tone };
});

const toneClass = computed(() => {
    switch (resolved.value.tone) {
        case 'success':
            return 'border-emerald-200/80 bg-emerald-50 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-100';
        case 'warning':
            return 'border-amber-200/80 bg-amber-50 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/35 dark:text-amber-100';
        case 'danger':
            return 'border-red-200/80 bg-red-50 text-red-900 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-100';
        case 'info':
            return 'border-sky-200/80 bg-sky-50 text-sky-950 dark:border-sky-900/60 dark:bg-sky-950/35 dark:text-sky-100';
        default:
            return 'border-border/80 bg-muted/50 text-foreground dark:bg-muted/30';
    }
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold tracking-wide shadow-sm"
        :class="toneClass"
    >
        <span
            class="size-1.5 shrink-0 rounded-full bg-current opacity-70"
            aria-hidden="true"
        />
        {{ resolved.label }}
    </span>
</template>
