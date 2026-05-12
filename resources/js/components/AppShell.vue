<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { AppVariant } from '@/types';
import type { Auth } from '@/types/auth';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const page = usePage<{ auth: Auth; sidebarOpen?: boolean }>();
const isOpen = page.props.sidebarOpen;

const isAdminShell = computed(() => page.props.auth?.roles?.includes('admin') ?? false);
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider
        v-else
        :default-open="isOpen"
        :class="cn(isAdminShell && 'admin-app-shell')"
    >
        <slot />
    </SidebarProvider>
</template>
