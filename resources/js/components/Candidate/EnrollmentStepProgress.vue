<script setup lang="ts">
import ProgressBar from 'primevue/progressbar';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        currentStep: string;
        totalSteps?: number;
        stepLabels?: string[];
    }>(),
    {
        totalSteps: 5,
        stepLabels: () => [
            'Ações afirmativas PcD',
            'Dados pessoais',
            'Formação e títulos',
            'Documentos obrigatórios',
            'Revisão e envio',
        ],
    },
);

const current = computed(() => {
    const n = Number.parseInt(props.currentStep, 10);

    return Number.isNaN(n) ? 1 : Math.min(Math.max(n, 1), props.totalSteps);
});

const progress = computed(() =>
    Math.round((current.value / props.totalSteps) * 100),
);

const currentLabel = computed(
    () => props.stepLabels[current.value - 1] ?? `Etapa ${current.value}`,
);
</script>

<template>
    <div
        class="mb-6 rounded-xl border border-border bg-muted/30 px-4 py-3 sm:px-5"
        role="status"
        :aria-label="`Progresso da inscrição: etapa ${current} de ${totalSteps}, ${currentLabel}`"
    >
        <div
            class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm"
        >
            <span class="font-medium text-foreground">
                Etapa {{ current }} de {{ totalSteps }}
            </span>
            <span class="text-muted-foreground">{{ currentLabel }}</span>
        </div>
        <ProgressBar :value="progress" :show-value="false" class="h-2" />
        <p class="mt-2 text-xs text-muted-foreground">
            Seu progresso é salvo ao avançar nas etapas. Você pode voltar para
            revisar antes de finalizar.
        </p>
    </div>
</template>
