<script setup lang="ts">
import { AtSign, Phone, PhoneCall } from 'lucide-vue-next';
import { computed } from 'vue';
import CandidateInfoCard from '@/components/Candidate/CandidateInfoCard.vue';
import CandidateReadonlyField from '@/components/Candidate/CandidateReadonlyField.vue';
import { asText  } from '@/components/Candidate/profileTypes';
import type {CandidateProfileUser} from '@/components/Candidate/profileTypes';

const props = defineProps<{
    user: CandidateProfileUser | null;
}>();

const fields = computed(() => {
    const u = props.user;

    return {
        email: asText(u?.email),
        telefone: asText(u?.telefone),
        telefoneFixo: asText(u?.telefone_fixo),
    };
});
</script>

<template>
    <CandidateInfoCard
        title="Contato"
        hint="Canais oficiais usados pela banca examinadora."
        :icon="PhoneCall"
        accent="sky"
    >
        <div class="grid grid-cols-1 gap-1">
            <CandidateReadonlyField
                label="E-mail"
                :value="fields.email"
                :icon="AtSign"
                hint="Verifique sua caixa para mensagens do processo."
            />
            <CandidateReadonlyField
                label="Telefone (celular)"
                :value="fields.telefone"
                :icon="Phone"
                mono
            />
            <CandidateReadonlyField
                label="Telefone fixo"
                :value="fields.telefoneFixo"
                :icon="Phone"
                mono
            />
        </div>
    </CandidateInfoCard>
</template>
