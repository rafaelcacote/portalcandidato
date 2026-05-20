<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CandidateDashboardHero from '@/components/Candidate/CandidateDashboardHero.vue';
import CandidateDashboardOngoingList from '@/components/Candidate/CandidateDashboardOngoingList.vue';
import CandidateDashboardPendenciesList from '@/components/Candidate/CandidateDashboardPendenciesList.vue';
import CandidateDashboardQuickActions from '@/components/Candidate/CandidateDashboardQuickActions.vue';
import CandidateDashboardStats from '@/components/Candidate/CandidateDashboardStats.vue';
import { home } from '@/routes';
import { dashboard } from '@/routes/candidate';
import type { Auth } from '@/types/auth';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel do candidato', href: dashboard.url() },
        ],
    },
});

const props = defineProps<{
    summary: {
        inscricoes_em_andamento: number;
        pendencias: number;
        mensagens_nao_lidas: number;
    };
    inscricoes_em_andamento: Array<{
        id: number;
        status: string;
        process_title: string;
        numero_protocolo: string | null;
    }>;
    pendencias_inscricao: Array<{
        id: number;
        process_title: string;
        numero_protocolo: string | null;
    }>;
    documentos_recusados: Array<{
        id: number;
        application_id: number;
        nome_arquivo: string;
        tipo_documento: string;
        process_title: string;
        motivo_recusa: string | null;
    }>;
    highlight_application: {
        id: number;
        process_title: string;
        status: string;
        numero_protocolo: string | null;
        kind: 'pendencia' | 'rascunho' | 'documento_recusado';
        detail?: string | null;
    } | null;
}>();

const page = usePage<{ auth: Auth }>();

const displayName = computed(() => {
    const name = page.props.auth?.user?.name?.trim() ?? 'Candidato';

    return name.split(/\s+/)[0] ?? name;
});
</script>

<template>
    <div class="min-h-0 flex-1 bg-background p-3 md:p-5">
        <Head title="Painel do candidato" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <CandidateDashboardHero
                :display-name="displayName"
                :inscricoes-em-andamento="summary.inscricoes_em_andamento"
                :pendencias="summary.pendencias"
                :highlight-application="highlight_application"
            />

            <CandidateDashboardStats :summary="summary" />

            <CandidateDashboardQuickActions />

            <div class="grid gap-5 lg:grid-cols-2">
                <CandidateDashboardOngoingList :applications="inscricoes_em_andamento" />
                <CandidateDashboardPendenciesList
                    :pendencias-inscricao="pendencias_inscricao"
                    :documentos-recusados="documentos_recusados"
                />
            </div>
        </div>
    </div>
</template>
