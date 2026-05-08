<script setup lang="ts">
import type { GlobalEvent } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { onMounted, onUnmounted } from 'vue';
import {
    flashTypeToSeverity,
    summaryForFlashType,
} from '@/lib/mapFlashToastToPrime';
import type { FlashToast } from '@/types/ui';

const primeToast = useToast();

const LIFE_MS = 5_000;

function isFlashToast(value: unknown): value is FlashToast {
    if (value === null || typeof value !== 'object') {
        return false;
    }

    const o = value as Record<string, unknown>;

    const typeOk =
        typeof o.type === 'string' &&
        ['success', 'info', 'warning', 'error'].includes(o.type);

    return typeOk && typeof o.message === 'string' && o.message.trim() !== '';
}

function handler(event: GlobalEvent<'flash'>): void {
    const rawToast = event.detail.flash.toast;

    if (!isFlashToast(rawToast)) {
        return;
    }

    primeToast.add({
        severity: flashTypeToSeverity(rawToast.type),
        summary: summaryForFlashType(rawToast.type),
        detail: rawToast.message,
        life: LIFE_MS,
        closable: true,
    });
}

let stopListening: (() => void) | undefined;

onMounted(() => {
    stopListening = router.on('flash', handler);
});

onUnmounted(() => stopListening?.());
</script>

<template>
    <div class="contents">
        <Toast position="top-right" />
    </div>
</template>
