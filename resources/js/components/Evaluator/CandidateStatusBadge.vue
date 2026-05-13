<script setup lang="ts">
import { CheckCircle2, Clock, XCircle, AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    status: string;
    size?: 'sm' | 'md' | 'lg';
}>();

const config = computed(() => {
    const map: Record<string, { label: string; classes: string; iconClasses: string; icon: unknown }> = {
        enviada: {
            label: 'Aguardando',
            classes: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200/80',
            iconClasses: 'text-sky-500',
            icon: Clock,
        },
        em_analise: {
            label: 'Em análise',
            classes: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/80',
            iconClasses: 'text-amber-500',
            icon: AlertCircle,
        },
        pendencia: {
            label: 'Doc. pendentes',
            classes: 'bg-orange-50 text-orange-700 ring-1 ring-orange-200/80',
            iconClasses: 'text-orange-500',
            icon: AlertCircle,
        },
        aprovada: {
            label: 'Aprovado',
            classes: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80',
            iconClasses: 'text-emerald-500',
            icon: CheckCircle2,
        },
        reprovada: {
            label: 'Reprovado',
            classes: 'bg-red-50 text-red-700 ring-1 ring-red-200/80',
            iconClasses: 'text-red-500',
            icon: XCircle,
        },
        rascunho: {
            label: 'Rascunho',
            classes: 'bg-slate-100 text-slate-500 ring-1 ring-slate-200/80',
            iconClasses: 'text-slate-400',
            icon: Clock,
        },
        aprovado: {
            label: 'Aprovado',
            classes: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80',
            iconClasses: 'text-emerald-500',
            icon: CheckCircle2,
        },
        recusado: {
            label: 'Recusado',
            classes: 'bg-red-50 text-red-700 ring-1 ring-red-200/80',
            iconClasses: 'text-red-500',
            icon: XCircle,
        },
        pendente: {
            label: 'Pendente',
            classes: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/80',
            iconClasses: 'text-amber-500',
            icon: Clock,
        },
        enviado: {
            label: 'Enviado',
            classes: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200/80',
            iconClasses: 'text-sky-500',
            icon: Clock,
        },
    };

    return (
        map[props.status] ?? {
            label: props.status,
            classes: 'bg-slate-100 text-slate-500 ring-1 ring-slate-200',
            iconClasses: 'text-slate-400',
            icon: Clock,
        }
    );
});

const sizeClasses = computed(() => {
    const s = props.size ?? 'md';
    return {
        sm: 'px-2 py-0.5 text-[10px] gap-1',
        md: 'px-2.5 py-1 text-xs gap-1.5',
        lg: 'px-3.5 py-1.5 text-sm gap-2',
    }[s];
});

const iconSize = computed(() => {
    const s = props.size ?? 'md';
    return { sm: 'size-3', md: 'size-3.5', lg: 'size-4' }[s];
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full font-semibold',
            config.classes,
            sizeClasses,
        ]"
    >
        <component
            :is="config.icon"
            :class="[iconSize, config.iconClasses]"
        />
        {{ config.label }}
    </span>
</template>
