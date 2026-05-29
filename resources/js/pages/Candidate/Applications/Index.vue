<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ClipboardCheck,
    FileText,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Heading from '@/components/Heading.vue';
import { home } from '@/routes';
import { index as applicationsIndex, show } from '@/routes/candidate/applications';
import { index as processesIndex } from '@/routes/candidate/processes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Minhas inscrições', href: applicationsIndex.url() },
        ],
    },
});

defineProps<{
    applications: {
        data: Array<{
            id: number;
            status: string;
            numero_protocolo: string | null;
            finalizada_em: string | null;
            comprovante_url?: string | null;
            created_at: string;
            selection_process?: { id: number; titulo: string; orgao?: string | null } | null;
            documents_count?: number;
            documents_pending_count?: number;
        }>;
        total: number;
        current_page: number;
        last_page: number;
    };
}>();

const statusSeverity: Record<string, 'secondary' | 'success' | 'warn' | 'danger'> = {
    rascunho: 'secondary',
    inscrita: 'success',
    em_analise: 'warn',
    pendencia: 'warn',
    aprovada: 'success',
    reprovada: 'danger',
    cancelada: 'secondary',
};

const statusLabel: Record<string, string> = {
    rascunho: 'Rascunho',
    inscrita: 'Inscrito',
    em_analise: 'Em análise',
    pendencia: 'Pendência',
    aprovada: 'Aprovado',
    reprovada: 'Reprovado',
    cancelada: 'Cancelada',
};

const statusIcon: Record<string, string> = {
    rascunho: 'pi-file-edit',
    inscrita: 'pi-check-circle',
    em_analise: 'pi-spin pi-spinner',
    pendencia: 'pi-exclamation-circle',
    aprovada: 'pi-verified',
    reprovada: 'pi-times-circle',
    cancelada: 'pi-ban',
};

function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}
</script>

<template>
    <div class="p-1">
        <Head title="Minhas inscrições" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Minhas inscrições"
                    description="Acompanhe o status, protocolo e pendências de cada inscrição."
                    :icon="ClipboardCheck"
                />
                <Link :href="processesIndex().url">
                    <Button
                        label="Processos abertos"
                        icon="pi pi-search"
                        severity="secondary"
                        outlined
                        size="small"
                    />
                </Link>
            </div>

            <!-- Lista vazia -->
            <div
                v-if="applications.data.length === 0"
                class="flex flex-col items-center justify-center gap-4 rounded-xl border border-border bg-card py-16 text-center"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-muted">
                    <i class="pi pi-inbox text-2xl text-muted-foreground" />
                </div>
                <div>
                    <p class="text-base font-semibold">Nenhuma inscrição</p>
                    <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                        Você ainda não se inscreveu em nenhum processo seletivo.
                        Explore os processos abertos e candidate-se.
                    </p>
                </div>
                <Link :href="processesIndex().url">
                    <Button
                        label="Ver processos abertos"
                        icon="pi pi-search"
                        size="small"
                    />
                </Link>
            </div>

            <!-- Cards de inscrições -->
            <div v-else class="flex flex-col gap-3">
                <Card
                    v-for="application in applications.data"
                    :key="application.id"
                    class="rounded-xl shadow-sm transition-shadow hover:shadow-md"
                >
                    <template #content>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <!-- Info principal -->
                            <div class="flex min-w-0 items-start gap-4">
                                <div
                                    :class="[
                                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                                        application.status === 'aprovada'
                                            ? 'bg-green-100 text-green-600 dark:bg-green-950/30 dark:text-green-400'
                                            : application.status === 'reprovada'
                                              ? 'bg-red-100 text-red-600 dark:bg-red-950/30 dark:text-red-400'
                                              : application.status === 'pendencia'
                                                ? 'bg-amber-100 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400'
                                                : 'bg-primary/10 text-primary',
                                    ]"
                                >
                                    <i :class="['pi', statusIcon[application.status] ?? 'pi-file']" class="text-lg" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-foreground">
                                        {{ application.selection_process?.titulo ?? '—' }}
                                    </p>
                                    <p v-if="application.selection_process?.orgao" class="mt-0.5 text-xs text-muted-foreground">
                                        {{ application.selection_process.orgao }}
                                    </p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                        <span v-if="application.numero_protocolo" class="flex items-center gap-1">
                                            <i class="pi pi-hashtag" />
                                            {{ application.numero_protocolo }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i class="pi pi-calendar" />
                                            Inscrito em {{ formatDate(application.created_at) }}
                                        </span>
                                        <span
                                            v-if="application.finalizada_em"
                                            class="flex items-center gap-1"
                                        >
                                            <i class="pi pi-check" />
                                            Finalizado em {{ formatDate(application.finalizada_em) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status e ações -->
                            <div class="flex shrink-0 items-center gap-2">
                                <a
                                    v-if="application.comprovante_url"
                                    v-tooltip.top="'Comprovante de inscrição (PDF)'"
                                    :href="application.comprovante_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex"
                                    @click.stop
                                >
                                    <Button
                                        icon="pi pi-file-pdf"
                                        rounded
                                        text
                                        size="small"
                                        severity="secondary"
                                    />
                                </a>
                                <Tag
                                    :value="statusLabel[application.status] ?? application.status"
                                    :severity="statusSeverity[application.status] ?? 'secondary'"
                                />
                                <Link :href="show({ application: application.id }).url">
                                    <Button
                                        v-tooltip.top="'Abrir inscrição'"
                                        icon="pi pi-arrow-right"
                                        rounded
                                        text
                                        size="small"
                                    />
                                </Link>
                            </div>
                        </div>

                        <!-- Barra de progresso por status -->
                        <div v-if="application.status === 'pendencia'" class="mt-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-900 dark:bg-amber-950/30">
                            <i class="pi pi-exclamation-triangle text-amber-500" />
                            <p class="text-xs font-medium text-amber-700 dark:text-amber-400">
                                Esta inscrição possui pendências. Acesse para ver detalhes.
                            </p>
                        </div>

                        <div v-if="application.status === 'reprovada'" class="mt-4 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 dark:border-red-950/30 dark:bg-red-950/30">
                            <i class="pi pi-times-circle text-red-500" />
                            <p class="text-xs font-medium text-red-700 dark:text-red-400">
                                Infelizmente esta inscrição não foi aprovada.
                            </p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Paginação -->
            <div
                v-if="applications.last_page > 1"
                class="flex items-center justify-center gap-2 py-2"
            >
                <span class="text-sm text-muted-foreground">
                    Exibindo página {{ applications.current_page }} de {{ applications.last_page }}
                    ({{ applications.total }} inscrições)
                </span>
            </div>
        </div>
    </div>
</template>
