<script setup lang="ts">
import { CloudUpload, Loader2 } from 'lucide-vue-next';
import Tooltip from 'primevue/tooltip';
import { computed } from 'vue';

const vTooltip = Tooltip;

const props = defineProps<{
    /** ISO timestamp from server (e.g. application.updated_at) */
    updatedAt?: string | null;
    /** When forms are actively saving */
    isSaving?: boolean;
}>();

const relativeLabel = computed(() => {
    if (props.isSaving) {
        return 'Salvando…';
    }

    if (!props.updatedAt) {
        return 'Sincronizado com o servidor';
    }

    const t = new Date(props.updatedAt).getTime();

    if (Number.isNaN(t)) {
        return 'Atualizado';
    }

    const diffMs = Date.now() - t;
    const sec = Math.floor(diffMs / 1000);
    const min = Math.floor(sec / 60);
    const hr = Math.floor(min / 60);

    if (sec < 45) {
        return 'há instantes';
    }

    if (min < 60) {
        return `há ${min} min`;
    }

    if (hr < 24) {
        return `há ${hr} h`;
    }

    return new Date(props.updatedAt).toLocaleString('pt-BR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const tooltip = computed(() => {
    if (!props.updatedAt) {
        return 'Os dados são gravados no servidor ao salvar cada etapa.';
    }

    return `Última atualização no servidor: ${new Date(props.updatedAt).toLocaleString('pt-BR')}`;
});
</script>

<template>
    <div
        v-tooltip.bottom="tooltip"
        class="inline-flex max-w-full items-center gap-2 rounded-full border border-border/60 bg-background/80 px-3 py-1.5 text-xs text-muted-foreground shadow-sm backdrop-blur-sm dark:bg-background/40"
    >
        <Loader2
            v-if="isSaving"
            :size="14"
            class="shrink-0 animate-spin text-primary"
            aria-hidden="true"
        />
        <CloudUpload v-else :size="14" class="shrink-0 text-primary/80" aria-hidden="true" />

        <span class="min-w-0 truncate">
            <span class="font-medium text-foreground">Salvamento automático</span>
            <span class="text-muted-foreground"> · {{ relativeLabel }}</span>
        </span>
    </div>
</template>
