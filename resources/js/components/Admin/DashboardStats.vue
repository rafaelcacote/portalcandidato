<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BarChart3, ClipboardList, FileText, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminSparkline from '@/components/Admin/AdminSparkline.vue';
import { index as applicationsIndex } from '@/routes/admin/applications';
import { index as evaluatorsIndex } from '@/routes/admin/evaluators';
import { index as processesIndex } from '@/routes/admin/processes';

const props = defineProps<{
    stats: {
        processes_total: number;
        processes_ativo: number;
        applications_total: number;
        applications_em_fluxo: number;
        evaluators_total: number;
        conversion_percent: number;
    };
    trend: number[];
}>();

const sparkSlices = computed(() => {
    const t = props.trend.length ? props.trend : [0];
    const chunk = 7;

    return [
        t.slice(0, chunk),
        t.slice(chunk, chunk * 2),
        t.slice(chunk * 2, chunk * 3),
        t.slice(chunk * 3),
    ].map((s) => (s.length ? s : [0]));
});

const cards = computed(() => [
    {
        label: 'Processos ativos',
        value: String(props.stats.processes_ativo),
        sub: `${props.stats.processes_total} cadastrados`,
        iconComponent: FileText,
        iconBg: 'bg-emerald-100',
        iconColor: 'text-emerald-700',
        strokeClass: 'stroke-emerald-500',
        fillClass: 'fill-emerald-500/15',
        glowColor: 'rgba(16,185,129,0.08)',
        sparkIdx: 0,
        href: processesIndex({ query: { status: 'ativo' } }).url,
    },
    {
        label: 'Inscrições',
        value: String(props.stats.applications_total),
        sub: `${props.stats.applications_em_fluxo} em análise`,
        iconComponent: ClipboardList,
        iconBg: 'bg-sky-100',
        iconColor: 'text-sky-700',
        strokeClass: 'stroke-sky-500',
        fillClass: 'fill-sky-500/15',
        glowColor: 'rgba(14,165,233,0.08)',
        sparkIdx: 1,
        href: applicationsIndex().url,
    },
    {
        label: 'Avaliadores',
        value: String(props.stats.evaluators_total),
        sub: 'Perfil de avaliador',
        iconComponent: Users,
        iconBg: 'bg-violet-100',
        iconColor: 'text-violet-700',
        strokeClass: 'stroke-violet-500',
        fillClass: 'fill-violet-500/15',
        glowColor: 'rgba(139,92,246,0.08)',
        sparkIdx: 2,
        href: evaluatorsIndex().url,
    },
    {
        label: 'Taxa de conversão',
        value: `${props.stats.conversion_percent}%`,
        sub: 'Aprovadas / enviadas',
        iconComponent: BarChart3,
        iconBg: 'bg-orange-100',
        iconColor: 'text-orange-700',
        strokeClass: 'stroke-orange-500',
        fillClass: 'fill-orange-500/15',
        glowColor: 'rgba(249,115,22,0.08)',
        sparkIdx: 3,
        href: null,
    },
]);
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <component
            :is="card.href ? Link : 'div'"
            v-for="card in cards"
            :key="card.label"
            :href="card.href ?? undefined"
            :class="[
                'group relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:ring-slate-300/60',
                card.href ? 'cursor-pointer' : '',
            ]"
        >
            <!-- Top row -->
            <div class="flex items-start justify-between gap-3">
                <div
                    :class="[
                        'flex size-11 shrink-0 items-center justify-center rounded-xl',
                        card.iconBg,
                        card.iconColor,
                    ]"
                >
                    <component :is="card.iconComponent" class="size-5" />
                </div>
                <!-- Sparkline top-right -->
                <div class="h-8 w-20 shrink-0">
                    <AdminSparkline
                        :values="sparkSlices[card.sparkIdx] ?? [0]"
                        :stroke-class="card.strokeClass"
                        :fill-class="card.fillClass"
                    />
                </div>
            </div>

            <!-- Value -->
            <div class="mt-4">
                <p class="text-xs font-semibold text-slate-500">
                    {{ card.label }}
                </p>
                <p
                    class="mt-1 text-3xl font-bold tracking-tight text-slate-900 tabular-nums"
                >
                    {{ card.value }}
                </p>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ card.sub }}</p>
            </div>

            <!-- Subtle bottom gradient accent -->
            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 h-24 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                :style="`background: linear-gradient(to top, ${card.glowColor}, transparent)`"
            />
        </component>
    </div>
</template>
