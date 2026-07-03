<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { UserCircle } from 'lucide-vue-next';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { computed } from 'vue';
import AdminCandidateProfileView from '@/components/Admin/AdminCandidateProfileView.vue';
import Heading from '@/components/Heading.vue';
import type { CandidateProfileUser } from '@/components/Candidate/profileTypes';
import { formatCpfDisplay } from '@/lib/brDocuments';
import { index as adminCandidatesIndex } from '@/routes/admin/candidates';

type CandidateSummary = {
    id: number;
    name: string;
    email: string;
    cpf?: string | null;
    ativo: boolean;
    email_verified: boolean;
    profile_complete: boolean;
    applications_count: number;
    created_at: string | null;
};

const props = defineProps<{
    candidate: CandidateSummary;
    profile: CandidateProfileUser;
}>();

const cpfLabel = computed(() => formatCpfDisplay(props.candidate.cpf ?? ''));
</script>

<template>
    <div class="px-4 py-3 sm:px-6 md:px-8 md:py-4 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex flex-col gap-4 py-3">
                <Link :href="adminCandidatesIndex().url" class="w-fit">
                    <Button
                        label="Voltar para candidatos"
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        text
                        size="small"
                    />
                </Link>

                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <Heading
                        :title="candidate.name"
                        description="Dados cadastrais informados no registro do candidato."
                        :icon="UserCircle"
                    />

                    <div class="flex flex-wrap gap-2">
                        <Tag
                            :value="
                                candidate.profile_complete
                                    ? 'Perfil completo'
                                    : 'Perfil incompleto'
                            "
                            :severity="
                                candidate.profile_complete
                                    ? 'success'
                                    : 'warn'
                            "
                        />
                        <Tag
                            :value="
                                candidate.email_verified
                                    ? 'E-mail verificado'
                                    : 'E-mail pendente'
                            "
                            :severity="
                                candidate.email_verified
                                    ? 'success'
                                    : 'secondary'
                            "
                        />
                        <Tag
                            :value="`${candidate.applications_count} inscrição(ões)`"
                            severity="info"
                        />
                    </div>
                </div>

                <div
                    class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted-foreground"
                >
                    <span v-if="cpfLabel !== '-'">CPF: {{ cpfLabel }}</span>
                    <span>{{ candidate.email }}</span>
                </div>
            </div>

            <AdminCandidateProfileView :profile="profile" />
        </div>
    </div>
</template>
