<script setup lang="ts">
import type { Component } from 'vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string;
        value: string | null | undefined;
        icon?: Component | null;
        /** Use a monospaced font for the value (CPF, CEP, RG, ...). */
        mono?: boolean;
        /** Optional secondary line shown below the value. */
        hint?: string | null;
        /** Placeholder shown when the value is missing. */
        placeholder?: string;
    }>(),
    {
        icon: null,
        mono: false,
        hint: null,
        placeholder: 'Não informado',
    },
);

const display = computed<string>(() => {
    if (props.value === null || props.value === undefined) {
        return props.placeholder;
    }

    const trimmed = String(props.value).trim();

    return trimmed === '' ? props.placeholder : trimmed;
});

const isMissing = computed<boolean>(() => display.value === props.placeholder);
</script>

<template>
    <div
        class="group flex min-w-0 items-start gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-muted/40"
    >
        <span
            v-if="icon"
            class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-muted/70 text-muted-foreground transition-colors group-hover:bg-primary/10 group-hover:text-primary dark:bg-muted/30"
            aria-hidden="true"
        >
            <component :is="icon" :size="14" stroke-width="2" />
        </span>

        <div class="min-w-0 flex-1">
            <p
                class="text-[10.5px] font-semibold uppercase tracking-[0.08em] text-muted-foreground"
            >
                {{ label }}
            </p>

            <p
                class="mt-0.5 break-words text-[15px] font-semibold leading-snug"
                :class="[
                    mono ? 'font-mono tracking-tight' : '',
                    isMissing
                        ? 'italic font-medium text-muted-foreground/70'
                        : 'text-foreground',
                ]"
                :title="display"
            >
                {{ display }}
            </p>

            <p
                v-if="hint"
                class="mt-0.5 truncate text-[11px] leading-snug text-muted-foreground"
                :title="hint"
            >
                {{ hint }}
            </p>
        </div>
    </div>
</template>
