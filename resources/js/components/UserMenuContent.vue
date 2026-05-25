<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { KeyRound, LogOut, Settings } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ChangePasswordDialog from '@/components/ChangePasswordDialog.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();

const page = usePage();
const ui = computed(() => page.props.ui);
const passwordDialogOpen = ref(false);
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                {{ ui.settings }}
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem
            class="cursor-pointer"
            data-test="change-password-menu-item"
            @select.prevent="passwordDialogOpen = true"
        >
            <KeyRound class="mr-2 h-4 w-4" />
            Alterar senha
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ ui.log_out }}
        </Link>
    </DropdownMenuItem>

    <ChangePasswordDialog v-model:open="passwordDialogOpen" />
</template>
