<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ExternalLink, ShieldCheck } from 'lucide-vue-next';
import Skeleton from 'primevue/skeleton';
import { computed } from 'vue';
import CandidateAddressSection from '@/components/Candidate/CandidateAddressSection.vue';
import CandidateContactSection from '@/components/Candidate/CandidateContactSection.vue';
import CandidateDocumentSection from '@/components/Candidate/CandidateDocumentSection.vue';
import CandidatePersonalInfoSection from '@/components/Candidate/CandidatePersonalInfoSection.vue';
import CandidateProfileCompletion from '@/components/Candidate/CandidateProfileCompletion.vue';
import CandidateProfileHero from '@/components/Candidate/CandidateProfileHero.vue';
import {
    getProfileCompletion
    
} from '@/components/Candidate/profileTypes';
import type {CandidateProfileUser} from '@/components/Candidate/profileTypes';

const props = defineProps<{
    user: CandidateProfileUser | null;
    editHref: string;
    isFinalized: boolean;
}>();

const completion = computed(() => getProfileCompletion(props.user));
const hasUser = computed(() => props.user !== null);
</script>

<template>
    <div class="flex flex-col gap-5">
        <template v-if="hasUser">
            <CandidateProfileHero
                :user="user"
                :edit-href="editHref"
                :is-finalized="isFinalized"
                :is-complete="completion.isComplete"
            />

            <CandidateProfileCompletion
                :filled="completion.filled"
                :total="completion.total"
                :percent="completion.percent"
                :missing="completion.missing"
                :is-complete="completion.isComplete"
            />

            <div class="grid grid-cols-12 gap-4 lg:gap-5">
                <div class="col-span-12 flex flex-col gap-4 lg:col-span-7 lg:gap-5">
                    <CandidatePersonalInfoSection :user="user" />
                    <CandidateAddressSection :user="user" />
                </div>

                <div class="col-span-12 flex flex-col gap-4 lg:col-span-5 lg:gap-5">
                    <CandidateContactSection :user="user" />
                    <CandidateDocumentSection :user="user" />
                </div>
            </div>

            <div
                class="flex flex-col gap-3 rounded-2xl border border-dashed border-border/70 bg-muted/15 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:bg-muted/10"
            >
                <div class="flex items-start gap-2.5 sm:items-center">
                    <span
                        class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary ring-1 ring-inset ring-primary/15"
                        aria-hidden="true"
                    >
                        <ShieldCheck :size="15" stroke-width="2.2" />
                    </span>
                    <p class="text-[12.5px] leading-snug text-muted-foreground">
                        Dados sincronizados do
                        <span class="font-medium text-foreground">seu perfil</span>.
                        Para alterá-los, abra as configurações.
                    </p>
                </div>

                <Link
                    v-if="!isFinalized"
                    :href="editHref"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 text-[12.5px] font-semibold text-foreground shadow-sm transition-colors hover:border-foreground/40 hover:bg-foreground/[0.04] dark:bg-background/40"
                >
                    Abrir perfil
                    <ExternalLink :size="13" stroke-width="2.2" aria-hidden="true" />
                </Link>
            </div>
        </template>

        <template v-else>
            <div
                class="rounded-3xl border border-border/60 bg-card px-5 py-5 shadow-sm dark:bg-card/60 sm:px-7"
            >
                <div class="flex items-center gap-4">
                    <Skeleton shape="circle" size="4rem" />
                    <div class="flex-1 space-y-2">
                        <Skeleton width="60%" height="1.25rem" />
                        <Skeleton width="40%" height="0.9rem" />
                    </div>
                </div>
            </div>

            <Skeleton width="100%" height="3.25rem" class="!rounded-2xl" />

            <div class="grid grid-cols-12 gap-4 lg:gap-5">
                <div class="col-span-12 lg:col-span-7">
                    <Skeleton width="100%" height="14rem" class="!rounded-2xl" />
                </div>
                <div class="col-span-12 lg:col-span-5">
                    <Skeleton width="100%" height="14rem" class="!rounded-2xl" />
                </div>
            </div>
        </template>
    </div>
</template>
