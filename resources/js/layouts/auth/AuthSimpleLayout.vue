<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Toaster } from '@/components/ui/sonner';

const page = usePage();

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        contentMaxWidth?: string;
        backdrop?: 'login-fundo' | '';
    }>(),
    {
        title: '',
        description: '',
        contentMaxWidth: 'max-w-sm',
        backdrop: '',
    },
);

/** Arte de login: prop explícita ou deteção da página (defineOptions nem sempre injeta props extra no layout). */
const showLoginArtBackdrop = computed(
    () =>
        props.backdrop === 'login-fundo' ||
        page.component === 'auth/Login' ||
        page.component === 'auth/VerifyEmail' ||
        page.component === 'auth/ForgotPassword' ||
        page.component === 'auth/ResetPassword',
);

/** Telas curtas com arte de fundo: menos respiro no topo (evita faixa vazia antes do conteúdo). */
const isCompactLoginBackdrop = computed(
    () =>
        page.component === 'auth/VerifyEmail' ||
        page.component === 'auth/ForgotPassword' ||
        page.component === 'auth/ResetPassword',
);
</script>

<template>
    <div
        :class="[
            'relative flex min-h-dvh flex-col items-center justify-start overflow-x-hidden sm:px-6 md:px-8',
            showLoginArtBackdrop
                ? isCompactLoginBackdrop
                    ? 'pl-[max(1rem,env(safe-area-inset-left,0px))] pr-[max(1rem,env(safe-area-inset-right,0px))] pb-[max(2rem,env(safe-area-inset-bottom,0px))] pt-[max(1.5rem,env(safe-area-inset-top,0px))] sm:px-6 sm:pb-12 sm:pt-7 md:pt-8 lg:px-8 lg:pb-12 lg:pt-10'
                    : 'pl-[max(1rem,env(safe-area-inset-left,0px))] pr-[max(1rem,env(safe-area-inset-right,0px))] pb-[max(2.5rem,env(safe-area-inset-bottom,0px))] pt-[max(5.5rem,env(safe-area-inset-top,0px))] sm:px-6 sm:pb-16 sm:pt-24 md:pt-28 lg:px-8 lg:pb-16 lg:pt-36'
                : 'px-4 pb-12 pt-6 md:pb-16 md:pt-8',
            showLoginArtBackdrop
                ? 'bg-[#eef6f7] dark:bg-slate-950'
                : 'bg-[#eaf4f5] bg-gradient-to-b from-[#e2f2f3] via-[#eef8f9] to-[#e8f3f4] dark:from-[#061414] dark:via-[#0a1818] dark:to-[#061212]',
        ]"
    >
        <!-- Fundo institucional: <img> garante carregamento; vinhetas suaves mantêm leitura do formulário -->
        <template v-if="showLoginArtBackdrop">
            <img
                src="/img/fundo_login.png"
                alt=""
                class="pointer-events-none absolute inset-0 z-0 size-full min-h-dvh object-cover object-center"
                loading="eager"
                decoding="async"
                fetchpriority="low"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-0 z-0 bg-gradient-to-b from-white/50 via-white/28 to-[#e6f3f5]/45 dark:from-slate-950/85 dark:via-slate-950/65 dark:to-slate-950/80"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-0 z-0 bg-[radial-gradient(ellipse_90%_70%_at_50%_42%,rgb(255_255_255/0.55)_0%,rgb(255_255_255/0.08)_58%,transparent_100%)] dark:bg-[radial-gradient(ellipse_90%_70%_at_50%_42%,rgb(15_23_42/0.45)_0%,transparent_70%)]"
                aria-hidden="true"
            />
        </template>

        <Toaster />
        <div class="relative z-10 w-full" :class="contentMaxWidth ?? 'max-w-sm'">
            <div class="flex w-full flex-col gap-6">
                <div
                    v-if="title || description"
                    class="space-y-2 text-center"
                >
                    <h1
                        v-if="title"
                        class="text-xl font-semibold tracking-tight text-foreground"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
