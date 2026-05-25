<script setup lang="ts">
import { CheckCircle2, Save } from 'lucide-vue-next';

defineProps<{
    processing: boolean;
}>();

const emit = defineEmits<{
    saveDraft: [];
    finalize: [];
}>();
</script>

<template>
    <div class="sticky bottom-0 z-20 border-t border-slate-200/80 bg-white/95 backdrop-blur-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
            <p class="text-xs text-slate-400">
                Aprovar ou recusar títulos atualiza a pontuação automaticamente. Critérios e observações exigem salvar.
            </p>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    :disabled="processing"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:border-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:opacity-50"
                    @click="emit('saveDraft')"
                >
                    <Save class="size-4" />
                    Salvar rascunho
                </button>

                <button
                    type="button"
                    :disabled="processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-600/25 transition-all hover:from-teal-500 hover:to-emerald-500 hover:shadow-lg hover:shadow-teal-600/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-400/60 focus-visible:ring-offset-2 disabled:opacity-50"
                    @click="emit('finalize')"
                >
                    <CheckCircle2 class="size-4" />
                    Finalizar avaliação
                </button>
            </div>
        </div>
    </div>
</template>
