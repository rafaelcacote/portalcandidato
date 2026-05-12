<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, BarChart3, FileText, Settings2, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { index as evaluatorsIndex } from '@/routes/admin/evaluators';
import { index as processesIndex } from '@/routes/admin/processes';
import { index as reportsIndex } from '@/routes/admin/reports';
import { index as documentTypesIndex } from '@/routes/admin/support-tables/document-types';

const actions = computed(() => [
    {
        title: 'Processos seletivos',
        description: 'Criar, editar e configurar editais e etapas dos processos.',
        href: processesIndex().url,
        icon: FileText,
        bgClass: 'bg-emerald-50 group-hover:bg-emerald-100/60',
        iconColor: 'text-emerald-700',
        arrowColor: 'text-emerald-500',
        borderColor: 'border-emerald-100',
        accentBar: 'bg-emerald-500',
    },
    {
        title: 'Tabelas de apoio',
        description: 'Gerenciar documentos e títulos utilizados nos processos.',
        href: documentTypesIndex().url,
        icon: Settings2,
        bgClass: 'bg-sky-50 group-hover:bg-sky-100/60',
        iconColor: 'text-sky-700',
        arrowColor: 'text-sky-500',
        borderColor: 'border-sky-100',
        accentBar: 'bg-sky-500',
    },
    {
        title: 'Avaliadores',
        description: 'Cadastrar e gerenciar avaliadores e suas atribuições.',
        href: evaluatorsIndex().url,
        icon: Users,
        bgClass: 'bg-violet-50 group-hover:bg-violet-100/60',
        iconColor: 'text-violet-700',
        arrowColor: 'text-violet-500',
        borderColor: 'border-violet-100',
        accentBar: 'bg-violet-500',
    },
    {
        title: 'Relatórios',
        description: 'Acompanhar indicadores e exportar relatórios da plataforma.',
        href: reportsIndex().url,
        icon: BarChart3,
        bgClass: 'bg-orange-50 group-hover:bg-orange-100/60',
        iconColor: 'text-orange-700',
        arrowColor: 'text-orange-500',
        borderColor: 'border-orange-100',
        accentBar: 'bg-orange-500',
    },
]);
</script>

<template>
    <div>
        <h2 class="mb-4 text-sm font-semibold tracking-tight text-slate-800">Ações rápidas</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <Link
                v-for="action in actions"
                :key="action.title"
                :href="action.href"
                class="group relative flex items-start gap-3.5 overflow-hidden rounded-xl border bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                :class="action.borderColor"
            >
                <!-- Accent bar left -->
                <div
                    class="absolute bottom-0 left-0 top-0 w-0.5 rounded-full opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                    :class="action.accentBar"
                />

                <div
                    :class="[
                        'flex size-10 shrink-0 items-center justify-center rounded-xl transition-colors duration-200',
                        action.bgClass,
                        action.iconColor,
                    ]"
                >
                    <component :is="action.icon" class="size-5" />
                </div>

                <div class="min-w-0 flex-1">
                    <h3
                        class="text-sm font-semibold text-slate-800 transition-colors duration-150 group-hover:text-slate-900"
                    >
                        {{ action.title }}
                    </h3>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                        {{ action.description }}
                    </p>
                </div>

                <ArrowRight
                    :class="[
                        'mt-0.5 size-4 shrink-0 opacity-0 transition-all duration-200 group-hover:translate-x-0.5 group-hover:opacity-100',
                        action.arrowColor,
                    ]"
                />
            </Link>
        </div>
    </div>
</template>
