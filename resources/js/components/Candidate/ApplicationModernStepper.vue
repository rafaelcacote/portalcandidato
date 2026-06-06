<script setup lang="ts">
import {
    Accessibility,
    Award,
    CheckCircle2,
    FileText,
    User,
} from 'lucide-vue-next';
import Step from 'primevue/step';
import StepList from 'primevue/steplist';
import StepPanels from 'primevue/steppanels';
import Stepper from 'primevue/stepper';
import type { Component } from 'vue';

const model = defineModel<string>({ required: true });

const props = defineProps<{
    stepStates: Record<string, 'done' | 'current' | 'upcoming'>;
    isReadOnly?: boolean;
}>();

const steps: Array<{ value: string; label: string; icon: Component }> = [
    { value: '1', label: 'Ações afirmativas', icon: Accessibility },
    { value: '2', label: 'Dados pessoais', icon: User },
    { value: '3', label: 'Formação e títulos', icon: Award },
    { value: '4', label: 'Documentos obrigatórios', icon: FileText },
    { value: '5', label: 'Revisão e envio', icon: CheckCircle2 },
];

function stepValueStr(value: string | number): string {
    return String(value);
}

function stateOf(value: string | number): 'done' | 'current' | 'upcoming' {
    return props.stepStates[stepValueStr(value)] ?? 'upcoming';
}

function canClick(value: string | number): boolean {
    if (props.isReadOnly) {
        return true;
    }

    const s = stateOf(value);

    return s === 'done' || s === 'current';
}

function onStepActivate(value: string | number): void {
    if (canClick(value)) {
        model.value = stepValueStr(value);
    }
}

function circleClass(value: string | number, activeFromPrime: boolean): string {
    const s = stateOf(value);
    const base =
        'relative flex size-11 shrink-0 items-center justify-center rounded-2xl border text-sm font-bold transition-all duration-200';

    if (s === 'current' || activeFromPrime) {
        return `${base} border-primary/40 bg-primary text-primary-foreground ring-4 ring-primary/15 scale-[1.02]`;
    }

    if (s === 'done') {
        return `${base} border-emerald-200/80 bg-emerald-50 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100`;
    }

    return `${base} border-border/80 bg-muted/40 text-muted-foreground dark:bg-muted/20`;
}
</script>

<template>
    <div class="application-modern-stepper">
        <Stepper v-model:value="model" linear>
            <StepList
                class="mb-2 flex w-full flex-col gap-2 border-0 bg-transparent p-0 sm:gap-3 md:mb-6 md:flex-row md:items-stretch md:justify-between md:gap-3"
            >
                <Step
                    v-for="s in steps"
                    :key="s.value"
                    :value="s.value"
                    class="flex-1 border-0 bg-transparent p-0"
                >
                    <template #default="{ active, value }">
                        <button
                            type="button"
                            class="group flex w-full flex-row items-center gap-3 rounded-2xl border border-transparent p-2 text-left transition-colors hover:border-border/80 hover:bg-muted/30 md:flex-col md:items-center md:gap-2.5 md:p-3 md:text-center"
                            :class="[
                                stateOf(value) === 'current' || active
                                    ? 'md:bg-primary/[0.04]'
                                    : '',
                                !canClick(value)
                                    ? 'cursor-default'
                                    : 'cursor-pointer',
                            ]"
                            :disabled="!canClick(value)"
                            @click="onStepActivate(value)"
                        >
                            <div :class="circleClass(value, active)">
                                <CheckCircle2
                                    v-if="stateOf(value) === 'done'"
                                    :size="20"
                                    class="shrink-0"
                                    aria-hidden="true"
                                />
                                <component
                                    :is="s.icon"
                                    v-else-if="
                                        stateOf(value) === 'upcoming' && !active
                                    "
                                    :size="18"
                                    class="shrink-0 opacity-75"
                                    aria-hidden="true"
                                />
                                <span v-else class="tabular-nums">{{
                                    value
                                }}</span>
                            </div>
                            <div class="min-w-0 flex-1 md:w-full">
                                <p
                                    class="text-xs leading-snug font-semibold sm:text-[13px]"
                                    :class="
                                        stateOf(value) === 'current' || active
                                            ? 'text-foreground'
                                            : 'text-muted-foreground group-hover:text-foreground'
                                    "
                                >
                                    {{ s.label }}
                                </p>
                                <p
                                    class="mt-0.5 hidden text-[11px] text-muted-foreground md:block"
                                >
                                    {{
                                        stateOf(value) === 'done'
                                            ? 'Concluída'
                                            : stateOf(value) === 'current'
                                              ? 'Em foco'
                                              : 'Pendente'
                                    }}
                                </p>
                            </div>
                        </button>
                    </template>
                </Step>
            </StepList>

            <StepPanels>
                <slot />
            </StepPanels>
        </Stepper>
    </div>
</template>

<style scoped>
.application-modern-stepper :deep(.p-stepper-separator),
.application-modern-stepper :deep(.p-steplist-separator) {
    display: none;
}

.application-modern-stepper :deep(.p-step-header) {
    width: 100%;
    padding: 0;
    border: none;
    background: transparent;
}

.application-modern-stepper :deep(.p-step) {
    flex: 1;
}
</style>
