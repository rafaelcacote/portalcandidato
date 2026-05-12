<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AtSign,
    BadgeCheck,
    CircleDashed,
    Lock,
    Pencil,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import Tooltip from 'primevue/tooltip';
import { computed } from 'vue';
import {
    asText,
    formatRelative,
    maskCpf
    
} from '@/components/Candidate/profileTypes';
import type {CandidateProfileUser} from '@/components/Candidate/profileTypes';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';

const vTooltip = Tooltip;

const props = defineProps<{
    user: CandidateProfileUser | null;
    editHref: string;
    isFinalized: boolean;
    isComplete: boolean;
}>();

const displayName = computed(() => asText(props.user?.name) ?? 'Candidato(a)');
const displayEmail = computed(() => asText(props.user?.email));
const cpfMasked = computed(() => maskCpf(props.user?.cpf));
const lastUpdate = computed(() => formatRelative(props.user?.updated_at));
const avatarSrc = computed<string | null>(() => {
    const v = props.user?.avatar ?? props.user?.foto_path;

    return typeof v === 'string' && v.trim() !== '' ? v : null;
});
const initials = computed(() => getInitials(displayName.value));
const isEmailVerified = computed(() => Boolean(asText(props.user?.email_verified_at)));
</script>

<template>
    <div
        class="relative overflow-hidden rounded-3xl border border-border/60 bg-gradient-to-br from-background via-card to-muted/40 px-5 py-5 shadow-sm dark:border-border/40 dark:from-card dark:via-card dark:to-muted/15 sm:px-7 sm:py-6"
    >
        <div
            class="pointer-events-none absolute -right-20 -top-24 size-72 rounded-full bg-primary/[0.07] blur-3xl"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -bottom-24 -left-16 size-64 rounded-full bg-violet-500/[0.05] blur-3xl dark:bg-violet-400/10"
            aria-hidden="true"
        />

        <div
            class="relative flex flex-col items-start gap-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex min-w-0 flex-1 items-center gap-4 sm:gap-5">
                <Avatar
                    class="size-16 shrink-0 overflow-hidden rounded-2xl ring-2 ring-background ring-offset-2 ring-offset-card sm:size-[68px] dark:ring-card"
                >
                    <AvatarImage v-if="avatarSrc" :src="avatarSrc" :alt="displayName" />
                    <AvatarFallback
                        class="rounded-2xl bg-gradient-to-br from-primary/20 via-primary/10 to-violet-500/15 text-base font-semibold tracking-wide text-foreground dark:from-primary/30 dark:via-primary/15 dark:to-violet-400/15"
                    >
                        {{ initials || '?' }}
                    </AvatarFallback>
                </Avatar>

                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                        Perfil do candidato
                    </p>
                    <h2
                        class="mt-0.5 truncate text-xl font-bold tracking-tight text-foreground sm:text-[22px]"
                        :title="displayName"
                    >
                        {{ displayName }}
                    </h2>

                    <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[12px]">
                        <span
                            v-if="cpfMasked"
                            v-tooltip.bottom="'CPF parcialmente mascarado por privacidade'"
                            class="inline-flex items-center gap-1.5 rounded-full border border-border/70 bg-background/70 px-2.5 py-1 font-mono font-medium text-foreground shadow-sm dark:bg-background/40"
                        >
                            <Lock :size="11" stroke-width="2.4" class="opacity-60" aria-hidden="true" />
                            <span class="tabular-nums tracking-tight">{{ cpfMasked }}</span>
                        </span>

                        <span
                            v-if="displayEmail"
                            v-tooltip.bottom="displayEmail"
                            class="inline-flex max-w-[260px] items-center gap-1.5 rounded-full border border-border/70 bg-background/70 px-2.5 py-1 font-medium text-foreground shadow-sm dark:bg-background/40"
                        >
                            <AtSign :size="11" stroke-width="2.4" class="shrink-0 opacity-60" aria-hidden="true" />
                            <span class="truncate">{{ displayEmail }}</span>
                        </span>

                        <span
                            v-if="isEmailVerified"
                            class="inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 font-semibold text-emerald-800 dark:text-emerald-300"
                        >
                            <BadgeCheck :size="11" stroke-width="2.5" aria-hidden="true" />
                            E-mail verificado
                        </span>

                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-full border px-2 py-1 font-semibold',
                                isComplete
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
                                    : 'border-amber-500/25 bg-amber-500/10 text-amber-900 dark:text-amber-200',
                            ]"
                        >
                            <BadgeCheck v-if="isComplete" :size="11" stroke-width="2.5" aria-hidden="true" />
                            <CircleDashed v-else :size="11" stroke-width="2.4" aria-hidden="true" />
                            {{ isComplete ? 'Perfil completo' : 'Perfil incompleto' }}
                        </span>
                    </div>

                    <p v-if="lastUpdate" class="mt-2 text-[11.5px] text-muted-foreground">
                        Atualizado <span class="font-medium text-foreground">{{ lastUpdate }}</span>
                    </p>
                </div>
            </div>

            <div class="flex w-full shrink-0 items-center gap-2 sm:w-auto sm:flex-col sm:items-end sm:gap-2.5">
                <Link
                    v-if="!isFinalized"
                    :href="editHref"
                    class="inline-flex w-full sm:w-auto"
                >
                    <Button
                        type="button"
                        size="small"
                        class="!w-full !rounded-xl !border-0 !bg-foreground !px-3.5 !py-2 !text-[13px] !font-semibold !text-background hover:!bg-foreground/90 sm:!w-auto dark:!bg-primary dark:!text-primary-foreground dark:hover:!bg-primary/90"
                    >
                        <template #default>
                            <span class="flex items-center gap-1.5">
                                <Pencil :size="14" stroke-width="2.4" aria-hidden="true" />
                                Editar perfil
                            </span>
                        </template>
                    </Button>
                </Link>

                <p
                    v-tooltip.bottom="'Os dados são gerenciados nas configurações do perfil. As alterações aparecem aqui na próxima visita.'"
                    class="hidden text-right text-[11px] leading-snug text-muted-foreground sm:block sm:max-w-[220px]"
                >
                    Os dados desta etapa vêm do seu perfil — somente leitura aqui.
                </p>
            </div>
        </div>
    </div>
</template>
