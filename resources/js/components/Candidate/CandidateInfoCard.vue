<script setup lang="ts">
import type { Component } from 'vue';
import { computed } from 'vue';

type Accent = 'primary' | 'emerald' | 'amber' | 'sky' | 'violet' | 'neutral';

const props = withDefaults(
    defineProps<{
        title: string;
        icon: Component;
        hint?: string | null;
        accent?: Accent;
    }>(),
    {
        hint: null,
        accent: 'primary',
    },
);

const accentBadgeClass = computed<string>(() => {
    switch (props.accent) {
        case 'emerald':
            return 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300 dark:ring-emerald-500/25';
        case 'amber':
            return 'bg-amber-500/10 text-amber-700 ring-amber-500/20 dark:text-amber-300 dark:ring-amber-500/25';
        case 'sky':
            return 'bg-sky-500/10 text-sky-700 ring-sky-500/20 dark:text-sky-300 dark:ring-sky-500/25';
        case 'violet':
            return 'bg-violet-500/10 text-violet-700 ring-violet-500/20 dark:text-violet-300 dark:ring-violet-500/25';
        case 'neutral':
            return 'bg-muted text-foreground ring-border';
        case 'primary':
        default:
            return 'bg-primary/10 text-primary ring-primary/20 dark:text-primary dark:ring-primary/25';
    }
});
</script>

<template>
    <section
        class="group/card relative flex flex-col overflow-hidden rounded-2xl border border-border/70 bg-card shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all duration-200 hover:border-border hover:shadow-[0_4px_16px_-6px_rgba(15,23,42,0.10)] dark:bg-card/60 dark:hover:border-border/80"
    >
        <header
            class="flex items-start gap-3 border-b border-border/50 px-5 pt-4 pb-3"
        >
            <span
                :class="[
                    'flex size-9 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset',
                    accentBadgeClass,
                ]"
                aria-hidden="true"
            >
                <component :is="icon" :size="17" stroke-width="2" />
            </span>

            <div class="min-w-0 flex-1">
                <h4
                    class="text-[13px] font-semibold tracking-tight text-foreground"
                >
                    {{ title }}
                </h4>
                <p
                    v-if="hint"
                    class="mt-0.5 line-clamp-2 text-[12px] leading-snug text-muted-foreground"
                >
                    {{ hint }}
                </p>
            </div>

            <slot name="header-action" />
        </header>

        <div class="flex-1 px-2.5 pt-2 pb-3 sm:px-3">
            <slot />
        </div>
    </section>
</template>
