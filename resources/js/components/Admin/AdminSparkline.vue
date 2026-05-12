<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        values: number[];
        strokeClass?: string;
        fillClass?: string;
    }>(),
    {
        strokeClass: 'stroke-emerald-500',
        fillClass: 'fill-emerald-500/15',
    },
);

const viewW = 120;
const viewH = 36;
const pad = 2;

const pathD = computed(() => {
    const vals = props.values.length ? props.values : [0];
    const max = Math.max(...vals, 1);
    const min = 0;
    const range = max - min || 1;
    const step = vals.length > 1 ? (viewW - pad * 2) / (vals.length - 1) : 0;

    const points = vals.map((v, i) => {
        const x = pad + i * step;
        const y = pad + (1 - (v - min) / range) * (viewH - pad * 2);

        return [x, y] as const;
    });

    const line = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ');
    const area =
        `${line} L ${points[points.length - 1]![0].toFixed(1)} ${viewH - pad} L ${points[0]![0].toFixed(1)} ${viewH - pad} Z`;

    return { line, area };
});
</script>

<template>
    <svg
        class="block w-full"
        :viewBox="`0 0 ${viewW} ${viewH}`"
        preserveAspectRatio="none"
        aria-hidden="true"
    >
        <path :d="pathD.area" :class="fillClass" stroke="none" />
        <path
            :d="pathD.line"
            fill="none"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            :class="strokeClass"
        />
    </svg>
</template>
