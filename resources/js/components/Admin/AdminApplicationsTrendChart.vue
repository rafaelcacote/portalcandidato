<script setup lang="ts">
import { computed, useId } from 'vue';

const props = withDefaults(
    defineProps<{
        values: number[];
    }>(),
    {},
);

const gradientId = `admin-dash-area-${useId()}`;

const viewW = 520;
const viewH = 200;
const padX = 8;
const padY = 12;

const paths = computed(() => {
    const vals = props.values.length ? props.values : [0];
    const max = Math.max(...vals, 1);
    const min = 0;
    const range = max - min || 1;
    const innerW = viewW - padX * 2;
    const innerH = viewH - padY * 2;
    const step = vals.length > 1 ? innerW / (vals.length - 1) : 0;

    const pts = vals.map((v, i) => {
        const x = padX + i * step;
        const y = padY + (1 - (v - min) / range) * innerH;

        return [x, y] as const;
    });

    const line = pts.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ');
    const last = pts[pts.length - 1]!;
    const first = pts[0]!;
    const area = `${line} L ${last[0].toFixed(1)} ${viewH - padY} L ${first[0].toFixed(1)} ${viewH - padY} Z`;

    return { line, area };
});
</script>

<template>
    <svg
        class="h-44 w-full md:h-52"
        :viewBox="`0 0 ${viewW} ${viewH}`"
        preserveAspectRatio="none"
        role="img"
        aria-label="Inscrições nos últimos 30 dias"
    >
        <defs>
            <linearGradient :id="gradientId" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="rgb(16 185 129)" stop-opacity="0.35" />
                <stop offset="100%" stop-color="rgb(16 185 129)" stop-opacity="0" />
            </linearGradient>
        </defs>
        <path :d="paths.area" :fill="`url(#${gradientId})`" stroke="none" />
        <path
            :d="paths.line"
            fill="none"
            stroke="rgb(5 150 105)"
            stroke-width="2.5"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>
