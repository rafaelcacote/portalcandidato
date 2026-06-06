<script setup lang="ts">
import {
    Building2,
    CalendarDays,
    Fingerprint,
    IdCard,
    MapPin,
} from 'lucide-vue-next';
import { computed } from 'vue';
import CandidateInfoCard from '@/components/Candidate/CandidateInfoCard.vue';
import CandidateReadonlyField from '@/components/Candidate/CandidateReadonlyField.vue';
import {
    asText,
    formatDateBR,
    maskRg,
} from '@/components/Candidate/profileTypes';
import type { CandidateProfileUser } from '@/components/Candidate/profileTypes';

const props = defineProps<{
    user: CandidateProfileUser | null;
}>();

const fields = computed(() => {
    const u = props.user;

    return {
        identidade: maskRg(u?.identidade),
        orgaoEmissor: asText(u?.orgao_emissor),
        identidadeUf: asText(u?.identidade_uf),
        emissao: formatDateBR(u?.identidade_data_emissao),
    };
});
</script>

<template>
    <CandidateInfoCard
        title="Documentação"
        hint="Documento de identificação informado no cadastro."
        :icon="IdCard"
        accent="violet"
    >
        <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
            <CandidateReadonlyField
                label="Identidade"
                :value="fields.identidade"
                :icon="Fingerprint"
                mono
                hint="Mascarado por privacidade"
                class="sm:col-span-2"
            />
            <CandidateReadonlyField
                label="Órgão emissor"
                :value="fields.orgaoEmissor"
                :icon="Building2"
            />
            <CandidateReadonlyField
                label="UF da identidade"
                :value="fields.identidadeUf"
                :icon="MapPin"
            />
            <CandidateReadonlyField
                label="Data de emissão"
                :value="fields.emissao"
                :icon="CalendarDays"
                class="sm:col-span-2"
            />
        </div>
    </CandidateInfoCard>
</template>
