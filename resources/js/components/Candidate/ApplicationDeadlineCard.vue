<script setup lang="ts">
import { CalendarClock } from 'lucide-vue-next';

defineProps<{
    label: string;
    /** Pre-formatted deadline text */
    deadlineText: string;
    /** Short hint under the date */
    hint?: string;
}>();
</script>

<template>
    <div
        class="flex items-start gap-3 rounded-xl border border-border/70 bg-card/60 p-4 shadow-sm backdrop-blur-sm dark:bg-card/40"
    >
        <div
            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-primary/15"
        >
            <CalendarClock :size="20" aria-hidden="true" />
        </div>
        <div class="min-w-0">
            <p
                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
            >
                {{ label }}
            </p>
            <p class="mt-1 text-sm font-semibold text-foreground">
                {{ deadlineText }}
            </p>
            <p
                v-if="hint"
                class="mt-0.5 text-xs leading-relaxed text-muted-foreground"
            >
                {{ hint }}
            </p>
        </div>
    </div>
</template>
