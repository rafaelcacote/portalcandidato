<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        /** Tailwind background class for the dot and ping ring (e.g. bg-green-500). */
        colorClass?: string;
    }>(),
    {
        colorClass: 'bg-green-500',
    },
);
</script>

<template>
    <!-- Slightly larger box so ping can expand without shifting the badge label -->
    <span
        :class="cn('relative inline-flex size-2 shrink-0 items-center justify-center', props.class)"
        aria-hidden="true"
    >
        <span
            :class="
                cn(
                    'pulse-loader-ring absolute left-1/2 top-1/2 size-1.5 rounded-full opacity-90',
                    colorClass,
                )
            "
        />
        <span
            :class="cn('relative z-[1] size-1.5 shrink-0 rounded-full', colorClass)"
        />
    </span>
</template>

<style scoped>
.pulse-loader-ring {
    animation: pulse-loader-ring 1.1s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes pulse-loader-ring {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.9;
    }

    100% {
        transform: translate(-50%, -50%) scale(3);
        opacity: 0;
    }
}
</style>
