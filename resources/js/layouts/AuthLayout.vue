<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        /** Tailwind max-width class for the form column (e.g. max-w-3xl for long forms). */
        contentMaxWidth?: string;
        /**
         * Arte de fundo em tela cheia (camadas + vinheta no layout, não na página).
         * @see AuthSimpleLayout
         */
        backdrop?: 'login-fundo' | '';
    }>(),
    {
        title: '',
        description: '',
        contentMaxWidth: 'max-w-sm',
        backdrop: '',
    },
);
</script>

<template>
    <AuthLayout
        :title="title"
        :description="description"
        :content-max-width="contentMaxWidth"
        :backdrop="backdrop"
    >
        <slot />
    </AuthLayout>
</template>
