<script setup lang="ts">
import { Award } from 'lucide-vue-next';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import type { ProcessTitleGroupRow } from '@/components/Candidate/processTitleTypes';

const props = withDefaults(
    defineProps<{
        titleGroups: ProcessTitleGroupRow[];
        variant?: 'default' | 'wizard';
    }>(),
    {
        variant: 'default',
    },
);

function formatScore(value: string | number): string {
    const n = typeof value === 'string' ? Number.parseFloat(value) : value;
    if (Number.isNaN(n)) {
        return String(value);
    }
    return n % 1 === 0 ? String(Math.trunc(n)) : n.toFixed(2).replace(/\.?0+$/, '');
}

function formatsLabel(formats: string[] | null): string {
    if (!formats?.length) {
        return 'PDF';
    }
    return formats.map((f) => f.toUpperCase()).join(', ');
}
</script>

<template>
    <div v-if="titleGroups.length > 0">
        <!-- Wizard: contêiner simples (evita Card dentro do stepper) -->
        <div
            v-if="variant === 'wizard'"
            class="space-y-4 rounded-xl border border-dashed border-primary/30 bg-muted/10 p-4 sm:p-5"
        >
            <div>
                <div class="flex items-center gap-2 text-base font-semibold text-foreground">
                    <Award :size="18" class="text-primary" />
                    Títulos para pontuação
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                    Confira os grupos e itens do edital. Envie os comprovantes na etapa de documentos
                    obrigatórios.
                </p>
            </div>

            <div class="flex flex-col gap-6">
                <section
                    v-for="group in titleGroups"
                    :key="group.id"
                    class="rounded-xl border border-border bg-card/80 p-4"
                >
                    <div class="flex flex-wrap items-start justify-between gap-2 border-b border-border pb-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-foreground">{{ group.name }}</p>
                            <p v-if="group.description" class="mt-1 text-sm text-muted-foreground">
                                {{ group.description }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="text-lg font-bold text-primary">{{ formatScore(group.max_score) }}</span>
                            <p class="text-xs text-muted-foreground">pts máx. do grupo</p>
                        </div>
                    </div>

                    <ul class="mt-3 flex flex-col gap-3">
                        <li
                            v-for="item in group.items"
                            :key="item.id"
                            class="rounded-lg border border-border/80 bg-muted/15 px-3 py-3"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="min-w-0 flex-1 text-sm font-medium leading-snug text-foreground">
                                    {{ item.title }}
                                </p>
                                <div class="flex shrink-0 flex-wrap gap-1">
                                    <Tag
                                        :value="`${formatScore(item.score_per_unit)} pts / ${item.score_unit}`"
                                        severity="secondary"
                                        class="text-xs"
                                    />
                                    <Tag
                                        v-if="item.requires_attachment"
                                        value="Comprovante"
                                        severity="warn"
                                        icon="pi pi-paperclip"
                                        class="text-xs"
                                    />
                                    <Tag v-else value="Sem anexo" severity="secondary" class="text-xs" />
                                </div>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                <span v-if="item.max_quantity != null">
                                    Até <strong class="text-foreground">{{ item.max_quantity }}</strong>
                                    unidade(s)
                                </span>
                                <span v-if="item.period_rule">Regra: {{ item.period_rule }}</span>
                                <span>
                                    Formatos: {{ formatsLabel(item.accepted_formats) }} · até
                                    {{ item.max_file_size_mb }} MB
                                </span>
                            </div>
                            <p
                                v-if="item.candidate_instructions"
                                class="mt-2 rounded-md bg-primary/5 px-2 py-1.5 text-xs leading-relaxed text-foreground"
                            >
                                {{ item.candidate_instructions }}
                            </p>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <Card v-else class="rounded-xl shadow-sm">
            <template #title>
                <div class="flex items-center gap-2">
                    <Award :size="16" class="text-muted-foreground" />
                    Títulos para pontuação
                </div>
            </template>
            <template #subtitle>
                <span class="text-sm font-normal text-muted-foreground">
                    Itens configurados pelo edital — organize comprovantes antes do envio.
                </span>
            </template>
            <template #content>
                <div class="flex flex-col gap-6">
                    <section
                        v-for="group in titleGroups"
                        :key="group.id"
                        class="rounded-xl border border-border bg-card/50 p-4"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-border pb-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-foreground">{{ group.name }}</p>
                                <p v-if="group.description" class="mt-1 text-sm text-muted-foreground">
                                    {{ group.description }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="text-lg font-bold text-primary">{{ formatScore(group.max_score) }}</span>
                                <p class="text-xs text-muted-foreground">pts máx. do grupo</p>
                            </div>
                        </div>

                        <ul class="mt-3 flex flex-col gap-3">
                            <li
                                v-for="item in group.items"
                                :key="item.id"
                                class="rounded-lg border border-border/80 bg-muted/15 px-3 py-3"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <p class="min-w-0 flex-1 text-sm font-medium leading-snug text-foreground">
                                        {{ item.title }}
                                    </p>
                                    <div class="flex shrink-0 flex-wrap gap-1">
                                        <Tag
                                            :value="`${formatScore(item.score_per_unit)} pts / ${item.score_unit}`"
                                            severity="secondary"
                                            class="text-xs"
                                        />
                                        <Tag
                                            v-if="item.requires_attachment"
                                            value="Comprovante"
                                            severity="warn"
                                            icon="pi pi-paperclip"
                                            class="text-xs"
                                        />
                                        <Tag v-else value="Sem anexo" severity="secondary" class="text-xs" />
                                    </div>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                    <span v-if="item.max_quantity != null">
                                        Até <strong class="text-foreground">{{ item.max_quantity }}</strong>
                                        unidade(s)
                                    </span>
                                    <span v-if="item.period_rule">Regra: {{ item.period_rule }}</span>
                                    <span>
                                        Formatos: {{ formatsLabel(item.accepted_formats) }} · até
                                        {{ item.max_file_size_mb }} MB
                                    </span>
                                </div>
                                <p
                                    v-if="item.candidate_instructions"
                                    class="mt-2 rounded-md bg-primary/5 px-2 py-1.5 text-xs leading-relaxed text-foreground"
                                >
                                    {{ item.candidate_instructions }}
                                </p>
                            </li>
                        </ul>
                    </section>
                </div>
            </template>
        </Card>
    </div>
</template>
