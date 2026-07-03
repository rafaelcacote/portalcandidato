<script setup lang="ts">
import {
    CalendarDays,
    Circle,
    Flag,
    Globe2,
    IdCard,
    UserCircle,
    UserRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import CandidateInfoCard from '@/components/Candidate/CandidateInfoCard.vue';
import CandidateReadonlyField from '@/components/Candidate/CandidateReadonlyField.vue';
import {
    asText,
    formatDateBR,
    maskCpf,
} from '@/components/Candidate/profileTypes';
import type { CandidateProfileUser } from '@/components/Candidate/profileTypes';
import { formatCpfDisplay } from '@/lib/brDocuments';

const props = withDefaults(
    defineProps<{
        user: CandidateProfileUser | null;
        maskSensitive?: boolean;
    }>(),
    {
        maskSensitive: true,
    },
);

const fields = computed(() => {
    const u = props.user;
    const cpfValue = asText(u?.cpf);

    return {
        name: asText(u?.name),
        cpf:
            cpfValue === null
                ? null
                : props.maskSensitive
                  ? maskCpf(cpfValue)
                  : formatCpfDisplay(cpfValue),
        nascimento: formatDateBR(u?.data_nascimento),
        sexo: asText(u?.sexo),
        naturalidade: asText(u?.naturalidade),
        nacionalidade: asText(u?.nacionalidade),
    };
});
</script>

<template>
    <CandidateInfoCard
        title="Informações pessoais"
        hint="Identificação básica que aparecerá na ficha de inscrição."
        :icon="UserCircle"
        accent="primary"
    >
        <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
            <CandidateReadonlyField
                label="Nome completo"
                :value="fields.name"
                :icon="UserRound"
                class="sm:col-span-2"
            />
            <CandidateReadonlyField
                label="CPF"
                :value="fields.cpf"
                :icon="IdCard"
                mono
                :hint="
                    maskSensitive ? 'Mascarado por privacidade' : undefined
                "
            />
            <CandidateReadonlyField
                label="Data de nascimento"
                :value="fields.nascimento"
                :icon="CalendarDays"
            />
            <CandidateReadonlyField
                label="Sexo"
                :value="fields.sexo"
                :icon="Circle"
            />
            <CandidateReadonlyField
                label="Nacionalidade"
                :value="fields.nacionalidade"
                :icon="Flag"
            />
            <CandidateReadonlyField
                label="Naturalidade"
                :value="fields.naturalidade"
                :icon="Globe2"
                class="sm:col-span-2"
            />
        </div>
    </CandidateInfoCard>
</template>
