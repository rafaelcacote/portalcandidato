<script setup lang="ts">
import { Award, CheckCircle2, Info, Inbox, TrendingUp } from 'lucide-vue-next';
import Accordion from 'primevue/accordion';
import AccordionContent from 'primevue/accordioncontent';
import AccordionHeader from 'primevue/accordionheader';
import AccordionPanel from 'primevue/accordionpanel';
import { computed, ref } from 'vue';
import CandidateTitleItemUpload from '@/components/Candidate/CandidateTitleItemUpload.vue';
import type { TitleUploadedDoc } from '@/components/Candidate/CandidateTitleItemUpload.vue';
import type { ProcessTitleGroupRow, ProcessTitleItemRow } from '@/components/Candidate/processTitleTypes';

const props = defineProps<{
    titleGroups: ProcessTitleGroupRow[];
    documents: TitleUploadedDoc[];
    applicationId: number;
    isFinalized?: boolean;
}>();

/** Todos os painéis fechados por padrão. */
const activeAccordionValues = ref<string[]>([]);

function getUploadedDocs(itemId: number): TitleUploadedDoc[] {
    return props.documents.filter(
        (d) =>
            typeof d.process_title_item_id === 'number' && d.process_title_item_id === itemId,
    );
}

function formatScore(value: string | number): string {
    const n = typeof value === 'string' ? Number.parseFloat(value) : value;

    if (Number.isNaN(n)) {
        return String(value);
    }

    return n % 1 === 0 ? String(Math.trunc(n)) : n.toFixed(2).replace(/\.?0+$/, '');
}

const totalItems = computed(() => {
    let count = 0;

    for (const g of props.titleGroups) {
        count += g.items.length;
    }

    return count;
});

const itemsWithUploadCount = computed(() => {
    let count = 0;

    for (const g of props.titleGroups) {
        for (const i of g.items) {
            const hasValidUpload = getUploadedDocs(i.id).some((d) => d.status !== 'recusado');

            if (hasValidUpload) {
                count += 1;
            }
        }
    }

    return count;
});

const totalUploadedFiles = computed(() => {
    let count = 0;

    for (const g of props.titleGroups) {
        for (const i of g.items) {
            count += getUploadedDocs(i.id).filter((d) => d.status !== 'recusado').length;
        }
    }

    return count;
});

function getGroupItemsWithUpload(group: ProcessTitleGroupRow): number {
    return group.items.filter((i) =>
        getUploadedDocs(i.id).some((d) => d.status !== 'recusado'),
    ).length;
}

function computeItemPreviewScore(item: ProcessTitleItemRow, docs: TitleUploadedDoc[]): number {
    const validDocs = docs.filter((d) => d.status !== 'recusado');
    if (validDocs.length === 0) {
        return 0;
    }

    const perUnit = Number(item.score_per_unit);
    if (!Number.isFinite(perUnit) || perUnit <= 0) {
        return 0;
    }

    let total = 0;
    for (const doc of validDocs) {
        let qty = Math.max(1, Number(doc.quantidade ?? 1));
        if (item.max_quantity != null) {
            qty = Math.min(qty, item.max_quantity);
        }
        total += perUnit * qty;
    }

    return Math.round(total * 100) / 100;
}

function computeGroupPreviewScore(group: ProcessTitleGroupRow): number {
    let groupTotal = 0;
    for (const item of group.items) {
        groupTotal += computeItemPreviewScore(item, getUploadedDocs(item.id));
    }

    const groupMax = Number(group.max_score);
    if (Number.isFinite(groupMax) && groupMax > 0) {
        groupTotal = Math.min(groupTotal, groupMax);
    }

    return Math.round(groupTotal * 100) / 100;
}

const groupPreviewScores = computed(() =>
    props.titleGroups.map((g) => ({
        id: g.id,
        name: g.name,
        score: computeGroupPreviewScore(g),
        maxScore: Number(g.max_score),
        itemsWithUpload: getGroupItemsWithUpload(g),
    })),
);

const groupPreviewScoreMap = computed((): Record<number, number> => {
    const map: Record<number, number> = {};

    for (const g of groupPreviewScores.value) {
        map[g.id] = g.score;
    }

    return map;
});

const totalPreviewScore = computed(() => {
    const total = groupPreviewScores.value.reduce((sum, g) => sum + g.score, 0);
    return Math.round(total * 100) / 100;
});

const hasAnyUploads = computed(() =>
    props.documents.some((d) => d.status !== 'recusado'),
);
</script>

<template>
    <div class="flex flex-col gap-5">
        <!-- Barra de resumo geral -->
        <div
            v-if="totalItems > 0"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border/60 bg-muted/20 px-4 py-3 dark:bg-muted/10"
        >
            <div class="flex items-center gap-2 text-[13px] text-muted-foreground">
                <Award :size="16" class="text-primary" aria-hidden="true" />
                <span>
                    <span class="font-semibold text-foreground tabular-nums">{{ itemsWithUploadCount }}</span>
                    de
                    <span class="font-semibold text-foreground tabular-nums">{{ totalItems }}</span>
                    item(ns) com comprovantes ·
                    <span class="font-semibold text-foreground tabular-nums">{{ totalUploadedFiles }}</span>
                    arquivo(s) no total
                </span>
            </div>
            <p class="text-[11.5px] text-muted-foreground">
                Você pode enviar mais de um arquivo por item, respeitando o limite do edital.
            </p>
        </div>

        <!-- Prévia de pontuação total -->
        <div
            v-if="hasAnyUploads && titleGroups.length > 0"
            class="overflow-hidden rounded-2xl border border-amber-200/70 bg-amber-50/50 dark:border-amber-800/30 dark:bg-amber-950/15"
        >
            <div
                class="flex items-center gap-2 border-b border-amber-200/60 bg-amber-100/50 px-4 py-2.5 dark:border-amber-800/30 dark:bg-amber-900/20"
            >
                <TrendingUp
                    :size="14"
                    class="shrink-0 text-amber-700 dark:text-amber-400"
                    aria-hidden="true"
                />
                <p class="text-[12.5px] font-semibold text-amber-900 dark:text-amber-200">
                    Prévia de pontuação — estimativa com base nos comprovantes enviados
                </p>
            </div>

            <div class="px-4 py-3">
                <ul class="flex flex-col gap-1.5">
                    <li
                        v-for="g in groupPreviewScores"
                        :key="g.id"
                        class="flex items-center justify-between gap-3 text-[12px]"
                    >
                        <span class="min-w-0 truncate text-amber-800 dark:text-amber-300">
                            {{ g.name }}
                        </span>
                        <span class="shrink-0 font-semibold tabular-nums text-amber-900 dark:text-amber-200">
                            {{ formatScore(g.score) }}
                            <span class="font-normal text-amber-700/70 dark:text-amber-400/70">
                                / {{ formatScore(g.maxScore) }} pts
                            </span>
                        </span>
                    </li>
                </ul>

                <div
                    class="mt-2.5 flex items-center justify-between gap-3 border-t border-amber-200/60 pt-2.5 dark:border-amber-800/30"
                >
                    <div class="flex items-center gap-1.5">
                        <Award
                            :size="13"
                            class="shrink-0 text-amber-700 dark:text-amber-400"
                            aria-hidden="true"
                        />
                        <span class="text-[12.5px] font-semibold text-amber-900 dark:text-amber-200">
                            Total estimado
                        </span>
                    </div>
                    <span class="text-sm font-bold tabular-nums text-amber-900 dark:text-amber-100">
                        {{ formatScore(totalPreviewScore) }} pts
                    </span>
                </div>

                <p
                    class="mt-2.5 flex items-start gap-1.5 text-[11px] leading-relaxed text-amber-700/80 dark:text-amber-400/80"
                >
                    <Info :size="12" class="mt-0.5 shrink-0" aria-hidden="true" />
                    Esta é apenas uma estimativa. A pontuação final será atribuída pelo avaliador após análise dos comprovantes, podendo ser diferente desta prévia.
                </p>
            </div>
        </div>

        <!-- Estado vazio -->
        <div
            v-if="titleGroups.length === 0"
            class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-border/70 bg-muted/10 px-6 py-10 text-center"
        >
            <Inbox :size="22" class="text-muted-foreground" aria-hidden="true" />
            <p class="text-sm font-semibold text-foreground">
                Nenhum item de título configurado
            </p>
            <p class="max-w-md text-[12.5px] text-muted-foreground">
                Este processo seletivo não possui tabela de títulos para pontuação. Você pode
                avançar para a próxima etapa.
            </p>
        </div>

        <!-- Accordion de grupos de títulos -->
        <Accordion
            v-else
            v-model:value="activeAccordionValues"
            :multiple="true"
            class="title-groups-accordion flex flex-col gap-3"
        >
            <AccordionPanel
                v-for="group in titleGroups"
                :key="group.id"
                :value="String(group.id)"
            >
                <AccordionHeader>
                    <!-- Conteúdo personalizado do cabeçalho -->
                    <div class="flex min-w-0 flex-1 items-center gap-3 pr-2">
                        <!-- Badge do código do grupo -->
                        <span
                            class="shrink-0 rounded-lg bg-primary/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.1em] text-primary"
                        >
                            {{ group.code }}
                        </span>

                        <!-- Nome do grupo -->
                        <p class="min-w-0 flex-1 truncate text-[13.5px] font-semibold leading-snug text-foreground">
                            {{ group.name }}
                        </p>

                        <!-- Estatísticas do grupo -->
                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                            <!-- Contagem de itens preenchidos -->
                            <span
                                class="hidden text-[11px] tabular-nums text-muted-foreground sm:inline"
                            >
                                {{ getGroupItemsWithUpload(group) }}/{{ group.items.length }} itens
                            </span>

                            <!-- Prévia de pontuação do grupo -->
                            <span
                                v-if="groupPreviewScoreMap[group.id] > 0"
                                class="inline-flex items-center gap-1 rounded-lg border border-amber-200/70 bg-amber-50/80 px-2 py-0.5 text-[10.5px] font-semibold tabular-nums text-amber-800 dark:border-amber-800/40 dark:bg-amber-950/20 dark:text-amber-300"
                            >
                                <TrendingUp :size="10" aria-hidden="true" />
                                ~{{ formatScore(groupPreviewScoreMap[group.id]) }} pts
                            </span>

                            <!-- Pontuação máxima do grupo -->
                            <span
                                class="shrink-0 rounded-lg border border-border/50 bg-background/70 px-2 py-0.5 text-[10.5px] tabular-nums text-muted-foreground"
                            >
                                máx {{ formatScore(group.max_score) }} pts
                            </span>

                            <!-- Indicador de grupo completo -->
                            <CheckCircle2
                                v-if="
                                    group.items.length > 0 &&
                                    getGroupItemsWithUpload(group) === group.items.length
                                "
                                :size="15"
                                class="shrink-0 text-emerald-600 dark:text-emerald-400"
                                aria-hidden="true"
                            />
                        </div>
                    </div>
                </AccordionHeader>

                <AccordionContent>
                    <!-- Descrição do grupo -->
                    <p
                        v-if="group.description"
                        class="mb-4 rounded-xl bg-muted/30 px-3 py-2.5 text-[12.5px] leading-relaxed text-muted-foreground"
                    >
                        {{ group.description }}
                    </p>

                    <!-- Grupo sem itens -->
                    <div
                        v-if="group.items.length === 0"
                        class="rounded-xl border border-dashed border-border/60 bg-muted/15 px-4 py-6 text-center text-[12.5px] text-muted-foreground"
                    >
                        Sem itens neste grupo no momento.
                    </div>

                    <!-- Itens do grupo -->
                    <div v-else class="flex flex-col gap-3">
                        <CandidateTitleItemUpload
                            v-for="item in group.items"
                            :key="item.id"
                            :item="item"
                            :application-id="applicationId"
                            :uploaded-docs="getUploadedDocs(item.id)"
                            :is-finalized="isFinalized"
                        />
                    </div>
                </AccordionContent>
            </AccordionPanel>
        </Accordion>
    </div>
</template>

<style scoped>
/* Cada painel renderizado como card separado */
.title-groups-accordion :deep(.p-accordionpanel) {
    border: 1px solid hsl(var(--border) / 0.7) !important;
    border-radius: 1rem !important;
    overflow: hidden;
    background: hsl(var(--card) / 0.5);
}

/* Botão do cabeçalho */
.title-groups-accordion :deep(.p-accordionheader) {
    padding: 0.875rem 1.25rem !important;
    border-radius: 0 !important;
    width: 100%;
    border-bottom: 1px solid transparent !important;
    transition: background-color 0.15s;
}

.title-groups-accordion :deep(.p-accordionheader:hover) {
    background-color: hsl(var(--muted) / 0.3) !important;
}

/* Cabeçalho quando o painel está aberto */
.title-groups-accordion :deep(.p-accordionpanel-active > .p-accordionheader) {
    background-color: hsl(var(--primary) / 0.04) !important;
    border-bottom-color: hsl(var(--border) / 0.55) !important;
}

/* Ícone chevron */
.title-groups-accordion :deep(.p-accordionheader-toggle-icon) {
    flex-shrink: 0;
    width: 1rem;
    height: 1rem;
    color: hsl(var(--muted-foreground));
}

/* Área de conteúdo */
.title-groups-accordion :deep(.p-accordioncontent-content) {
    padding: 1rem 1.25rem 1.25rem !important;
}
</style>
