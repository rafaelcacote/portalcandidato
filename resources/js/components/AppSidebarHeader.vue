<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { useUserAvatar } from '@/composables/useUserAvatar';
import type { BreadcrumbItem } from '@/types';
import type { Auth } from '@/types/auth';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage<{ auth: Auth }>();
const auth = computed(() => page.props.auth);
const { avatarUrl, hasAvatar } = useUserAvatar(() => auth.value?.user);

const usesStaffHeader = computed(() => {
    const roles = auth.value?.roles ?? [];

    return roles.includes('admin') || roles.includes('avaliador');
});

</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-3 border-b border-sidebar-border/40 bg-background/80 px-4 backdrop-blur-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-5"
    >
        <template v-if="usesStaffHeader">
            <SidebarTrigger class="-ml-0.5 shrink-0" />
            <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
                <DropdownMenu v-if="auth.user">
                    <DropdownMenuTrigger :as-child="true">
                        <Button
                            variant="ghost"
                            class="h-auto max-w-full gap-2 rounded-xl px-2 py-1.5 hover:bg-slate-100"
                            data-test="header-user-menu"
                        >
                            <Avatar class="size-8 shrink-0 overflow-hidden rounded-lg">
                                <AvatarImage
                                    v-if="hasAvatar"
                                    :src="avatarUrl!"
                                    :alt="auth.user.name"
                                />
                                <AvatarFallback
                                    class="rounded-lg bg-emerald-600/15 font-semibold text-emerald-800"
                                >
                                    {{ getInitials(auth.user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                class="hidden max-w-[11rem] truncate text-left text-sm font-medium sm:inline"
                            >
                                {{ auth.user.name }}
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </template>

        <template v-else>
            <div class="flex min-w-0 flex-1 items-center gap-2">
                <SidebarTrigger class="-ml-1 shrink-0" />
                <template v-if="breadcrumbs && breadcrumbs.length > 0">
                    <Breadcrumbs :breadcrumbs="breadcrumbs" />
                </template>
            </div>

            <div v-if="auth.user" class="flex shrink-0 items-center">
                <DropdownMenu>
                    <DropdownMenuTrigger :as-child="true">
                        <Button
                            variant="ghost"
                            class="h-auto max-w-full gap-2 rounded-lg px-2 py-1.5 hover:bg-accent"
                            data-test="header-user-menu"
                        >
                            <Avatar class="size-8 shrink-0 overflow-hidden rounded-lg">
                                <AvatarImage
                                    v-if="hasAvatar"
                                    :src="avatarUrl!"
                                    :alt="auth.user.name"
                                />
                                <AvatarFallback
                                    class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                >
                                    {{ getInitials(auth.user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                class="hidden max-w-[12rem] truncate text-left text-sm font-medium sm:inline"
                            >
                                {{ auth.user.name }}
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </template>
    </header>
</template>
