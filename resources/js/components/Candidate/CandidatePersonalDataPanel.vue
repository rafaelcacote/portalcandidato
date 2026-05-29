<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';
import Button from 'primevue/button';
import Skeleton from 'primevue/skeleton';
import { computed, nextTick, ref } from 'vue';
import CandidateAddressSection from '@/components/Candidate/CandidateAddressSection.vue';
import CandidateContactSection from '@/components/Candidate/CandidateContactSection.vue';
import CandidateDocumentSection from '@/components/Candidate/CandidateDocumentSection.vue';
import CandidatePersonalInfoSection from '@/components/Candidate/CandidatePersonalInfoSection.vue';
import CandidateProfileCompletion from '@/components/Candidate/CandidateProfileCompletion.vue';
import CandidateProfileForm from '@/components/Candidate/CandidateProfileForm.vue';
import CandidateProfileHero from '@/components/Candidate/CandidateProfileHero.vue';
import {
    getProfileCompletion,
    type CandidateProfileUser,
} from '@/components/Candidate/profileTypes';

const props = defineProps<{
    user: CandidateProfileUser | null;
    ufs: string[];
    mustVerifyEmail: boolean;
    isFinalized: boolean;
}>();

const isEditing = ref(false);

const completion = computed(() => getProfileCompletion(props.user));
const hasUser = computed(() => props.user !== null);

const profileFormKey = computed(() => {
    const u = props.user;

    return u ? `${u.updated_at ?? ''}-${u.email ?? ''}` : 'empty';
});

function startEditing(): void {
    if (props.isFinalized || !hasUser.value) {
        return;
    }

    isEditing.value = true;
}

async function stopEditing(): Promise<void> {
    isEditing.value = false;
    await nextTick();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

defineExpose({
    startEditing,
});
</script>

<template>
    <div class="flex flex-col gap-5">
        <template v-if="hasUser">
            <CandidateProfileHero
                :user="user"
                :is-finalized="isFinalized"
                :is-complete="completion.isComplete"
                inline-edit
                @edit="startEditing"
            />

            <template v-if="!isEditing">
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
                    class="flex flex-col gap-3 rounded-2xl border border-dashed border-border/70 bg-muted/15 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
                >
                    <p class="text-[12.5px] leading-snug text-muted-foreground">
                        Revise seus dados abaixo. Se precisar alterar algo, edite aqui mesmo — você
                        não precisa sair desta inscrição.
                    </p>

                    <Button
                        v-if="!isFinalized"
                        type="button"
                        label="Editar dados"
                        icon="pi pi-pencil"
                        size="small"
                        outlined
                        class="shrink-0"
                        @click="startEditing"
                    />
                </div>
            </template>

            <div
                v-else
                class="rounded-2xl border border-primary/20 bg-primary/[0.02] p-4 sm:p-5"
            >
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            aria-hidden="true"
                        >
                            <Pencil :size="15" stroke-width="2.2" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-foreground">Editar perfil</p>
                            <p class="text-xs text-muted-foreground">
                                Atualize seus dados e salve, ou cancele para continuar sem alterar.
                            </p>
                        </div>
                    </div>
                </div>

                <CandidateProfileForm
                    :key="profileFormKey"
                    embedded
                    id-prefix="inscricao-perfil-"
                    :profile="user"
                    :ufs="ufs"
                    :must-verify-email="mustVerifyEmail"
                    @saved="stopEditing"
                    @cancel="stopEditing"
                />
            </div>
        </template>

        <template v-else>
            <div
                class="rounded-3xl border border-border/60 bg-card px-5 py-5 shadow-sm sm:px-7"
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
