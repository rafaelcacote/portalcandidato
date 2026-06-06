<script setup lang="ts">
import {
    Building,
    Globe2,
    Hash,
    Home,
    Mailbox,
    MapPin,
    MapPinned,
} from 'lucide-vue-next';
import { computed } from 'vue';
import CandidateInfoCard from '@/components/Candidate/CandidateInfoCard.vue';
import CandidateReadonlyField from '@/components/Candidate/CandidateReadonlyField.vue';
import { asText } from '@/components/Candidate/profileTypes';
import type { CandidateProfileUser } from '@/components/Candidate/profileTypes';

const props = defineProps<{
    user: CandidateProfileUser | null;
}>();

const fields = computed(() => {
    const u = props.user;

    return {
        endereco: asText(u?.endereco),
        numero: asText(u?.endereco_numero),
        bairro: asText(u?.bairro),
        cep: asText(u?.cep),
        cidade: asText(u?.cidade),
        uf: asText(u?.endereco_uf),
        pais: asText(u?.pais),
    };
});
</script>

<template>
    <CandidateInfoCard
        title="Endereço"
        hint="Correspondência e localização declarada."
        :icon="MapPinned"
        accent="emerald"
    >
        <div class="grid grid-cols-1 gap-1 sm:grid-cols-6">
            <CandidateReadonlyField
                label="Logradouro"
                :value="fields.endereco"
                :icon="Home"
                class="sm:col-span-4"
            />
            <CandidateReadonlyField
                label="Número"
                :value="fields.numero"
                :icon="Hash"
                mono
                class="sm:col-span-2"
            />
            <CandidateReadonlyField
                label="Bairro"
                :value="fields.bairro"
                :icon="Building"
                class="sm:col-span-3"
            />
            <CandidateReadonlyField
                label="CEP"
                :value="fields.cep"
                :icon="Mailbox"
                mono
                class="sm:col-span-3"
            />
            <CandidateReadonlyField
                label="Cidade"
                :value="fields.cidade"
                :icon="MapPin"
                class="sm:col-span-3"
            />
            <CandidateReadonlyField
                label="UF"
                :value="fields.uf"
                :icon="MapPin"
                mono
                class="sm:col-span-1"
            />
            <CandidateReadonlyField
                label="País"
                :value="fields.pais"
                :icon="Globe2"
                class="sm:col-span-2"
            />
        </div>
    </CandidateInfoCard>
</template>
