<script setup lang="ts">
import { Sparkles } from 'lucide-vue-next';
import ApplicationStatusBadge from '@/components/Candidate/ApplicationStatusBadge.vue';

defineProps<{
    processTitle: string;
    processTypeLabel: string | null;
    status: string;
    hasStarted: boolean;
}>();
</script>

<template>
    <div class="relative min-w-0 flex-1">
        <div
            class="mb-3 inline-flex items-center gap-2 rounded-full border border-primary/15 bg-primary/5 px-3 py-1 text-[11px] font-semibold tracking-[0.2em] text-primary/90 uppercase dark:border-primary/25 dark:bg-primary/10"
        >
            <Sparkles :size="12" class="opacity-80" aria-hidden="true" />
            Inscrição
        </div>

        <h1
            class="text-2xl font-bold tracking-tight text-balance text-foreground sm:text-3xl lg:text-4xl lg:leading-[1.15]"
        >
            {{ processTitle }}
        </h1>

        <div class="mt-4 flex flex-wrap items-center gap-2 sm:gap-3">
            <span
                v-if="processTypeLabel"
                class="inline-flex items-center rounded-lg border border-border/70 bg-muted/30 px-2.5 py-1 text-xs font-medium text-muted-foreground"
            >
                {{ processTypeLabel }}
            </span>
            <ApplicationStatusBadge
                :status="status"
                :has-started="hasStarted"
            />
        </div>
    </div>
</template>
