<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ClipboardList,
    LayoutGrid,
    MessageSquare,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Heading from '@/components/Heading.vue';
import { home } from '@/routes';
import { dashboard } from '@/routes/candidate';
import {
    index as applicationsIndex,
    show as applicationShow,
} from '@/routes/candidate/applications';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Painel do candidato', href: dashboard.url() },
        ],
    },
});

defineProps<{
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
}>();

function statusInscricaoLabel(status: string): string {
    const labels: Record<string, string> = {
        rascunho: 'Rascunho',
        em_analise: 'Em análise',
    };

    return labels[status] ?? status;
}
</script>

<template>
    <div class="p-1">
        <Head title="Painel do candidato" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Painel do candidato"
                    description="Resumo das suas inscrições, pendências e mensagens."
                    :icon="LayoutGrid"
                />
                <Link :href="applicationsIndex.url()">
                    <Button
                        label="Minhas inscrições"
                        icon="pi pi-list"
                        severity="secondary"
                        outlined
                        size="small"
                    />
                </Link>
            </div>

            <Card class="overflow-hidden rounded-xl shadow-md">
                <template #title>Resumo</template>
                <template #content>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div
                            class="flex items-start gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <ClipboardList :size="20" />
                            </div>
                            <div class="flex min-w-0 flex-col">
                                <p class="text-xs text-muted-foreground">
                                    Inscrições em andamento
                                </p>
                                <p
                                    class="mt-1 text-2xl font-semibold tabular-nums tracking-tight"
                                >
                                    {{ summary.inscricoes_em_andamento }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Rascunhos e inscrições em análise.
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <AlertTriangle :size="20" />
                            </div>
                            <div class="flex min-w-0 flex-col">
                                <p class="text-xs text-muted-foreground">
                                    Pendências
                                </p>
                                <p
                                    class="mt-1 text-2xl font-semibold tabular-nums tracking-tight"
                                >
                                    {{ summary.pendencias }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Inscrições com pendência e documentos
                                    recusados.
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <MessageSquare :size="20" />
                            </div>
                            <div class="flex min-w-0 flex-col">
                                <p class="text-xs text-muted-foreground">
                                    Mensagens
                                </p>
                                <p
                                    class="mt-1 text-2xl font-semibold tabular-nums tracking-tight"
                                >
                                    {{ summary.mensagens_nao_lidas }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Notificações não lidas (quando disponível).
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid gap-5 lg:grid-cols-2">
                <Card class="rounded-xl shadow-md">
                    <template #title>Inscrições em andamento</template>
                    <template #subtitle>
                        <Link
                            :href="applicationsIndex.url()"
                            class="text-sm font-medium text-primary no-underline hover:underline"
                        >
                            Ver todas
                        </Link>
                    </template>
                    <template #content>
                        <ul
                            v-if="inscricoes_em_andamento.length"
                            class="divide-y divide-border"
                        >
                            <li
                                v-for="row in inscricoes_em_andamento"
                                :key="row.id"
                                class="py-3 first:pt-0 last:pb-0"
                            >
                                <Link
                                    :href="
                                        applicationShow.url({
                                            application: row.id,
                                        })
                                    "
                                    class="group block rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    <p
                                        class="font-medium text-foreground group-hover:text-primary"
                                    >
                                        {{ row.process_title }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ statusInscricaoLabel(row.status) }}
                                        <span v-if="row.numero_protocolo">
                                            · {{ row.numero_protocolo }}
                                        </span>
                                    </p>
                                </Link>
                            </li>
                        </ul>
                        <div
                            v-else
                            class="flex flex-col items-center justify-center gap-3 px-6 py-10 text-center"
                        >
                            <i
                                class="pi pi-inbox text-3xl text-muted-foreground"
                            />
                            <p class="text-base font-medium">
                                Nenhuma inscrição em andamento
                            </p>
                            <p class="max-w-md text-sm text-muted-foreground">
                                Quando você iniciar ou tiver inscrições em
                                análise, elas aparecerão aqui.
                            </p>
                        </div>
                    </template>
                </Card>

                <Card class="rounded-xl shadow-md">
                    <template #title>Pendências</template>
                    <template #subtitle>
                        <Link
                            :href="applicationsIndex.url()"
                            class="text-sm font-medium text-primary no-underline hover:underline"
                        >
                            Minhas inscrições
                        </Link>
                    </template>
                    <template #content>
                        <div v-if="pendencias_inscricao.length" class="mb-6">
                            <p
                                class="mb-2 text-xs font-medium uppercase text-muted-foreground"
                            >
                                Inscrições
                            </p>
                            <ul
                                class="divide-y divide-border rounded-lg border border-border"
                            >
                                <li
                                    v-for="row in pendencias_inscricao"
                                    :key="'p-' + row.id"
                                    class="px-3 py-2"
                                >
                                    <Link
                                        :href="
                                            applicationShow.url({
                                                application: row.id,
                                            })
                                        "
                                        class="text-sm font-medium text-primary hover:underline"
                                    >
                                        {{ row.process_title }}
                                    </Link>
                                    <p
                                        v-if="row.numero_protocolo"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ row.numero_protocolo }}
                                    </p>
                                </li>
                            </ul>
                        </div>

                        <div v-if="documentos_recusados.length">
                            <p
                                class="mb-2 text-xs font-medium uppercase text-muted-foreground"
                            >
                                Documentos recusados
                            </p>
                            <ul
                                class="divide-y divide-border rounded-lg border border-border"
                            >
                                <li
                                    v-for="doc in documentos_recusados"
                                    :key="'d-' + doc.id"
                                    class="px-3 py-2"
                                >
                                    <Link
                                        :href="
                                            applicationShow.url({
                                                application: doc.application_id,
                                            })
                                        "
                                        class="text-sm font-medium text-primary hover:underline"
                                    >
                                        {{ doc.tipo_documento }}
                                    </Link>
                                    <p class="text-xs text-muted-foreground">
                                        {{ doc.process_title }} ·
                                        {{ doc.nome_arquivo }}
                                    </p>
                                    <p
                                        v-if="doc.motivo_recusa"
                                        class="mt-1 line-clamp-2 text-xs text-red-600"
                                    >
                                        {{ doc.motivo_recusa }}
                                    </p>
                                </li>
                            </ul>
                        </div>

                        <div
                            v-if="
                                !pendencias_inscricao.length &&
                                !documentos_recusados.length
                            "
                            class="flex flex-col items-center justify-center gap-3 px-6 py-10 text-center"
                        >
                            <i
                                class="pi pi-check-circle text-3xl text-muted-foreground"
                            />
                            <p class="text-base font-medium">
                                Nenhuma pendência
                            </p>
                            <p class="max-w-md text-sm text-muted-foreground">
                                Você não possui inscrições em pendência nem
                                documentos recusados no momento.
                            </p>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>
