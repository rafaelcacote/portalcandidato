<script setup lang="ts">
import { Award, Inbox } from 'lucide-vue-next';
import { computed } from 'vue';
import CandidateTitleItemUpload from '@/components/Candidate/CandidateTitleItemUpload.vue';
import type {TitleUploadedDoc} from '@/components/Candidate/CandidateTitleItemUpload.vue';
import type { ProcessTitleGroupRow } from '@/components/Candidate/processTitleTypes';

const props = defineProps<{
    titleGroups: ProcessTitleGroupRow[];
    documents: TitleUploadedDoc[];
    applicationId: number;
    isFinalized?: boolean;
}>();

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
</script>

<template>
    <div class="flex flex-col gap-5">
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
                Você pode enviar mais de um arquivo por item, respeitando o limite definido pelo edital.
            </p>
        </div>

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

        <section
            v-for="group in titleGroups"
            v-else
            :key="group.id"
            class="flex flex-col gap-3 rounded-2xl border border-border/70 bg-card/50 p-4 sm:p-5 dark:bg-card/30"
        >
            <header class="flex flex-wrap items-start justify-between gap-3 border-b border-border/60 pb-3">
                <div class="min-w-0 flex-1">
                    <p class="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                        Grupo {{ group.code }}
                    </p>
                    <h4 class="mt-0.5 text-[15px] font-semibold leading-snug text-foreground">
                        {{ group.name }}
                    </h4>
                    <p
                        v-if="group.description"
                        class="mt-1 text-[12.5px] leading-relaxed text-muted-foreground"
                    >
                        {{ group.description }}
                    </p>
                </div>
                <div
                    class="shrink-0 rounded-xl border border-border/70 bg-background/70 px-3 py-2 text-right shadow-sm dark:bg-background/40"
                >
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Pontuação máx.
                    </p>
                    <p class="text-base font-bold tabular-nums text-primary">
                        {{ formatScore(group.max_score) }} pts
                    </p>
                </div>
            </header>

            <div
                v-if="group.items.length === 0"
                class="rounded-xl border border-dashed border-border/60 bg-muted/15 px-4 py-6 text-center text-[12.5px] text-muted-foreground"
            >
                Sem itens neste grupo no momento.
            </div>

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
        </section>
    </div>
</template>
