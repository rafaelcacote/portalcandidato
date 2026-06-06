<script setup lang="ts">
import { AlertCircle, Award, FileStack, Gauge } from 'lucide-vue-next';
import Card from 'primevue/card';
import ProgressBar from 'primevue/progressbar';
import { computed } from 'vue';

const props = defineProps<{
    progressPercent: number;
    documentsCount: number;
    titlesCount: number;
    pendingCount: number;
}>();

const progressSafe = computed(() =>
    Math.min(100, Math.max(0, Math.round(props.progressPercent))),
);
</script>

<template>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5 lg:gap-4">
        <Card
            class="col-span-2 overflow-hidden rounded-2xl border border-border/60 shadow-sm lg:col-span-2 dark:border-border/40"
            :pt="{
                body: { class: 'p-4 sm:p-5' },
            }"
        >
            <template #content>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Gauge :size="20" aria-hidden="true" />
                        </div>
                        <div>
                            <p
                                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Progresso geral
                            </p>
                            <p
                                class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums"
                            >
                                {{ progressSafe }}%
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Conclusão da sua ficha de inscrição
                            </p>
                        </div>
                    </div>
                </div>
                <ProgressBar
                    :value="progressSafe"
                    :show-value="false"
                    class="mt-4 h-2 overflow-hidden rounded-full"
                />
            </template>
        </Card>

        <Card
            class="rounded-2xl border border-border/60 shadow-sm dark:border-border/40"
            :pt="{ body: { class: 'p-4' } }"
        >
            <template #content>
                <div class="flex items-center gap-2 text-muted-foreground">
                    <FileStack :size="16" aria-hidden="true" />
                    <span
                        class="text-[11px] font-semibold tracking-wider uppercase"
                        >Documentos</span
                    >
                </div>
                <p class="mt-3 text-2xl font-bold text-foreground tabular-nums">
                    {{ documentsCount }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Arquivos enviados
                </p>
            </template>
        </Card>

        <Card
            class="rounded-2xl border border-border/60 shadow-sm dark:border-border/40"
            :pt="{ body: { class: 'p-4' } }"
        >
            <template #content>
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Award :size="16" aria-hidden="true" />
                    <span
                        class="text-[11px] font-semibold tracking-wider uppercase"
                        >Títulos</span
                    >
                </div>
                <p class="mt-3 text-2xl font-bold text-foreground tabular-nums">
                    {{ titlesCount }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Comprovantes opcionais
                </p>
            </template>
        </Card>

        <Card
            class="rounded-2xl border border-border/60 shadow-sm dark:border-border/40"
            :pt="{ body: { class: 'p-4' } }"
        >
            <template #content>
                <div class="flex items-center gap-2 text-muted-foreground">
                    <AlertCircle :size="16" aria-hidden="true" />
                    <span
                        class="text-[11px] font-semibold tracking-wider uppercase"
                        >Pendências</span
                    >
                </div>
                <p
                    class="mt-3 text-2xl font-bold tabular-nums"
                    :class="
                        pendingCount > 0
                            ? 'text-amber-700 dark:text-amber-400'
                            : 'text-foreground'
                    "
                >
                    {{ pendingCount }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Itens a resolver
                </p>
            </template>
        </Card>
    </div>
</template>
