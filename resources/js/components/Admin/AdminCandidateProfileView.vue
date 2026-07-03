<script setup lang="ts">
import CandidateAddressSection from '@/components/Candidate/CandidateAddressSection.vue';
import CandidateContactSection from '@/components/Candidate/CandidateContactSection.vue';
import CandidateDocumentSection from '@/components/Candidate/CandidateDocumentSection.vue';
import CandidatePersonalInfoSection from '@/components/Candidate/CandidatePersonalInfoSection.vue';
import CandidateProfileCompletion from '@/components/Candidate/CandidateProfileCompletion.vue';
import CandidateProfileHero from '@/components/Candidate/CandidateProfileHero.vue';
import {
    getProfileCompletion,
    type CandidateProfileUser,
} from '@/components/Candidate/profileTypes';
import { computed } from 'vue';

const props = defineProps<{
    profile: CandidateProfileUser;
}>();

const completion = computed(() => getProfileCompletion(props.profile));
</script>

<template>
    <div class="flex flex-col gap-5">
        <CandidateProfileHero
            :user="profile"
            :is-finalized="true"
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
            <div
                class="col-span-12 flex flex-col gap-4 lg:col-span-7 lg:gap-5"
            >
                <CandidatePersonalInfoSection
                    :user="profile"
                    :mask-sensitive="false"
                />
                <CandidateAddressSection :user="profile" />
            </div>

            <div
                class="col-span-12 flex flex-col gap-4 lg:col-span-5 lg:gap-5"
            >
                <CandidateContactSection :user="profile" />
                <CandidateDocumentSection
                    :user="profile"
                    :mask-sensitive="false"
                />
            </div>
        </div>
    </div>
</template>
