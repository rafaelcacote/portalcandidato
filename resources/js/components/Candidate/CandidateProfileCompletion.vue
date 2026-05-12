<script setup lang="ts">
import { CheckCircle2, Sparkles } from 'lucide-vue-next';
import Tooltip from 'primevue/tooltip';
import { computed } from 'vue';

const vTooltip = Tooltip;

const props = defineProps<{
    filled: number;
    total: number;
    percent: number;
    missing: string[];
    isComplete: boolean;
}>();

const remaining = computed(() => Math.max(props.total - props.filled, 0));

const trackColorClass = computed<string>(() => {
    if (props.isComplete) {
        return 'bg-emerald-500/85 dark:bg-emerald-400';
    }

    if (props.percent >= 70) {
        return 'bg-primary';
    }

    if (props.percent >= 40) {
        return 'bg-amber-500/85 dark:bg-amber-400';
    }

    return 'bg-rose-500/80 dark:bg-rose-400';
});

const tooltipContent = computed<string>(() => {
    if (props.isComplete) {
        return 'Todos os campos do perfil estão preenchidos.';
    }

    if (props.missing.length === 0) {
        return 'Perfil incompleto.';
    }

    const head = props.missing.slice(0, 6).join(' · ');
    const extra = props.missing.length > 6 ? ` (+${props.missing.length - 6})` : '';

    return `Faltam preencher: ${head}${extra}`;
});
</script>

<template>
    <div
        v-tooltip.bottom="tooltipContent"
        class="flex w-full items-center gap-3 rounded-2xl border border-border/60 bg-card/70 px-4 py-3 shadow-sm backdrop-blur-sm dark:bg-card/40"
    >
        <span
            :class="[
                'flex size-9 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset',
                isComplete
                    ? 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300'
                    : 'bg-primary/10 text-primary ring-primary/20',
            ]"
            aria-hidden="true"
        >
            <CheckCircle2 v-if="isComplete" :size="17" stroke-width="2.2" />
            <Sparkles v-else :size="16" stroke-width="2" />
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex items-baseline justify-between gap-3">
                <p class="truncate text-[13px] font-semibold tracking-tight text-foreground">
                    {{ isComplete ? 'Perfil completo' : 'Completude do perfil' }}
                </p>
                <p class="shrink-0 text-[12px] tabular-nums text-muted-foreground">
                    <span class="text-[15px] font-bold text-foreground">{{ percent }}%</span>
                    <span class="ml-1.5 hidden sm:inline">
                        · {{ filled }}/{{ total }}
                    </span>
                </p>
            </div>

            <div
                class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted dark:bg-muted/50"
            >
                <div
                    class="h-full rounded-full transition-[width] duration-500 ease-out"
                    :class="trackColorClass"
                    :style="{ width: `${Math.max(percent, 4)}%` }"
                />
            </div>

            <p v-if="!isComplete" class="mt-1.5 text-[11px] leading-snug text-muted-foreground">
                <span class="font-medium text-foreground">{{ remaining }}</span>
                campo(s) ainda
                <span class="hidden sm:inline">precisam ser preenchidos no seu perfil.</span>
                <span class="sm:hidden">a preencher.</span>
            </p>
            <p v-else class="mt-1.5 text-[11px] leading-snug text-muted-foreground">
                Tudo certo — seus dados estão prontos para a inscrição.
            </p>
        </div>
    </div>
</template>
