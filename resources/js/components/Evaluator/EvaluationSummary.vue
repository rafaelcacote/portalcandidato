<script setup lang="ts">
import { CheckCircle2, Clock, FileText, Search, XCircle } from 'lucide-vue-next';
import Select from 'primevue/select';
import { computed } from 'vue';
import type { EvaluatorApplicationDocument } from '@/components/Evaluator/evaluatorDocumentTypes';

const props = defineProps<{
    documents: EvaluatorApplicationDocument[];
    search: string;
    statusFilter: string;
    categoryFilter: string;
    categoryOptions: Array<{ label: string; value: string }>;
}>();

const emit = defineEmits<{
    'update:search': [value: string];
    'update:statusFilter': [value: string];
    'update:categoryFilter': [value: string];
}>();

const stats = computed(() => {
    const total = props.documents.length;
    const approved = props.documents.filter((d) => d.status === 'aprovado').length;
    const refused = props.documents.filter((d) => d.status === 'recusado').length;
    const pending = total - approved - refused;
    return { total, approved, refused, pending };
});

const statusOptions = [
    { label: 'Todos os status', value: 'all' },
    { label: 'Aprovados', value: 'aprovado' },
    { label: 'Pendentes', value: 'pendente' },
    { label: 'Recusados', value: 'recusado' },
];
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80 shadow-sm">
        <!-- Stats row -->
        <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 px-5 py-4 sm:gap-6 sm:px-6">
            <div class="flex-1">
                <p class="text-sm font-bold text-slate-800">Resumo da avaliação</p>
                <p class="mt-0.5 text-xs text-slate-500">Acompanhe o status geral da documentação enviada.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Total -->
                <div class="flex items-center gap-2">
                    <div class="flex size-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                        <FileText class="size-4" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-400">Total de documentos</p>
                        <p class="text-lg font-bold tabular-nums leading-tight text-slate-900">
                            {{ stats.total }}
                        </p>
                    </div>
                </div>

                <div class="h-8 w-px bg-slate-200" />

                <!-- Aprovados -->
                <div class="flex items-center gap-1.5">
                    <CheckCircle2 class="size-4 text-emerald-500" />
                    <span class="text-sm font-bold tabular-nums text-emerald-600">{{ stats.approved }}</span>
                    <span class="text-xs text-slate-400">Aprovados</span>
                </div>

                <!-- Pendentes -->
                <div class="flex items-center gap-1.5">
                    <Clock class="size-4 text-amber-500" />
                    <span class="text-sm font-bold tabular-nums text-amber-600">{{ stats.pending }}</span>
                    <span class="text-xs text-slate-400">Pendentes</span>
                </div>

                <!-- Recusados -->
                <div class="flex items-center gap-1.5">
                    <XCircle class="size-4 text-red-500" />
                    <span class="text-sm font-bold tabular-nums text-red-600">{{ stats.refused }}</span>
                    <span class="text-xs text-slate-400">Recusados</span>
                </div>
            </div>
        </div>

        <!-- Filter row -->
        <div class="flex flex-wrap items-center gap-3 px-5 py-3.5 sm:px-6">
            <!-- Search -->
            <div class="relative min-w-[14rem] flex-1">
                <Search class="absolute left-3 top-1/2 size-3.5 -translate-y-1/2 text-slate-400" />
                <input
                    :value="search"
                    type="search"
                    placeholder="Buscar documento..."
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-2 pl-8.5 pr-3 text-sm text-slate-700 outline-none placeholder:text-slate-400 focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-400/20"
                    @input="emit('update:search', ($event.target as HTMLInputElement).value)"
                />
            </div>

            <!-- Status filter -->
            <Select
                :model-value="statusFilter"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                :pt="{
                    root: { class: 'min-w-[10.5rem] rounded-xl border border-slate-200 bg-slate-50/70 text-sm focus:border-teal-400 focus:ring-2 focus:ring-teal-400/20' },
                    label: { class: 'py-2 text-sm text-slate-700' },
                }"
                @update:model-value="emit('update:statusFilter', $event)"
            />

            <!-- Category filter -->
            <Select
                :model-value="categoryFilter"
                :options="categoryOptions"
                option-label="label"
                option-value="value"
                :pt="{
                    root: { class: 'min-w-[13rem] rounded-xl border border-slate-200 bg-slate-50/70 text-sm focus:border-teal-400 focus:ring-2 focus:ring-teal-400/20' },
                    label: { class: 'py-2 text-sm text-slate-700' },
                }"
                @update:model-value="emit('update:categoryFilter', $event)"
            />
        </div>
    </div>
</template>
