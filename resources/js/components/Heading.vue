<script setup lang="ts">
import type { Component } from 'vue';

type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    icon?: Component;
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? '' : 'mb-8 space-y-0.5'">
        <div class="flex items-center gap-2">
            <component
                :is="icon"
                v-if="icon"
                :class="
                    variant === 'small'
                        ? 'size-4 text-muted-foreground'
                        : 'size-5 text-muted-foreground'
                "
            />
            <h2
                :class="
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-xl font-semibold tracking-tight'
                "
            >
                {{ title }}
            </h2>
        </div>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
