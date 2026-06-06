<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Hash, Link2, Sparkles } from 'lucide-vue-next';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Tooltip from 'primevue/tooltip';
import ApplicationAutoSaveInfo from '@/components/Candidate/ApplicationAutoSaveInfo.vue';
import ApplicationDeadlineCard from '@/components/Candidate/ApplicationDeadlineCard.vue';
import ApplicationHero from '@/components/Candidate/ApplicationHero.vue';

const vTooltip = Tooltip;

defineProps<{
    processTitle: string;
    processTypeLabel: string | null;
    status: string;
    hasStarted: boolean;
    protocol: string | null;
    deadlineText: string;
    deadlineHint?: string;
    completedSteps: number;
    totalSteps: number;
    editalUrl: string | null;
    backHref: string;
    updatedAt?: string | null;
    isSaving?: boolean;
    isFinalized: boolean;
}>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-2xl border border-border/60 bg-gradient-to-br from-card via-card to-muted/25 shadow-sm dark:border-border/40 dark:from-card dark:via-card dark:to-muted/10"
    >
        <div
            class="pointer-events-none absolute -top-24 -right-24 size-[320px] rounded-full bg-primary/[0.07] blur-3xl dark:bg-primary/10"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -bottom-32 -left-16 size-[280px] rounded-full bg-violet-500/[0.06] blur-3xl dark:bg-violet-400/10"
            aria-hidden="true"
        />

        <div class="relative flex flex-col gap-6 p-5 sm:p-7 lg:p-8">
            <div
                class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between"
            >
                <ApplicationHero
                    :process-title="processTitle"
                    :process-type-label="processTypeLabel"
                    :status="status"
                    :has-started="hasStarted"
                />

                <div
                    class="flex w-full shrink-0 flex-col gap-3 lg:w-auto lg:items-end"
                >
                    <ApplicationAutoSaveInfo
                        :updated-at="updatedAt"
                        :is-saving="isSaving"
                    />
                    <div
                        class="flex w-full flex-wrap items-center justify-end gap-2"
                    >
                        <a
                            v-if="editalUrl"
                            v-tooltip.bottom="'PDF oficial do processo'"
                            :href="editalUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex"
                        >
                            <Button
                                label="Baixar edital"
                                icon="pi pi-file-pdf"
                                severity="secondary"
                                outlined
                                size="small"
                                class="shrink-0"
                            />
                        </a>
                        <Link :href="backHref" class="inline-flex">
                            <Button
                                label="Voltar"
                                icon="pi pi-arrow-left"
                                severity="secondary"
                                outlined
                                size="small"
                                class="shrink-0"
                            />
                        </Link>
                    </div>
                </div>
            </div>

            <Divider class="my-0 border-border/50" />

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <ApplicationDeadlineCard
                    label="Prazo final"
                    :deadline-text="deadlineText"
                    :hint="deadlineHint"
                />

                <div
                    class="flex items-start gap-3 rounded-xl border border-border/70 bg-card/60 p-4 shadow-sm backdrop-blur-sm dark:bg-card/40"
                >
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted text-foreground"
                    >
                        <Link2 :size="20" aria-hidden="true" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Protocolo
                        </p>
                        <p
                            class="mt-1 font-mono text-sm font-semibold break-all text-foreground"
                        >
                            {{ protocol ?? 'Será gerado ao enviar' }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-start gap-3 rounded-xl border border-border/70 bg-card/60 p-4 shadow-sm backdrop-blur-sm dark:bg-card/40"
                >
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-primary/15"
                    >
                        <Hash :size="20" aria-hidden="true" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Etapas concluídas
                        </p>
                        <p class="mt-1 text-sm font-semibold text-foreground">
                            <span class="text-lg font-bold tabular-nums">{{
                                completedSteps
                            }}</span>
                            <span class="text-muted-foreground">
                                / {{ totalSteps }}</span
                            >
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Marcos principais da inscrição
                        </p>
                    </div>
                </div>

                <div
                    v-if="!isFinalized"
                    class="flex items-start gap-3 rounded-xl border border-dashed border-primary/25 bg-primary/[0.04] p-4 dark:border-primary/30 dark:bg-primary/[0.07]"
                >
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-background/80 text-primary shadow-sm"
                    >
                        <Sparkles :size="20" aria-hidden="true" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Lembrete
                        </p>
                        <p
                            class="mt-1 text-sm leading-snug font-medium text-foreground"
                        >
                            Avance etapa a etapa. Você pode voltar para revisar
                            antes de enviar.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="isFinalized"
                class="mt-2 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-center text-sm font-medium text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100"
            >
                Inscrição registrada com sucesso.
            </div>
        </div>
    </header>
</template>
