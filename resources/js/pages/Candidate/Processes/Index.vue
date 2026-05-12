<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Building2, Calendar, FileText } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import Heading from '@/components/Heading.vue';
import { home } from '@/routes';
import { index as processesIndex } from '@/routes/candidate/processes';
import { show } from '@/routes/candidate/processes';
import { start } from '@/routes/candidate/applications';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Processos seletivos', href: processesIndex().url },
        ],
    },
});

defineProps<{
    processes: {
        data: Array<{
            id: number;
            titulo: string;
            descricao: string;
            status: string;
            orgao?: string | null;
            area?: string | null;
            inscricao_inicio_em: string | null;
            inscricao_fim_em: string | null;
            edital_download_url?: string | null;
        }>;
        total: number;
        current_page: number;
        last_page: number;
    };
}>();

const statusSeverity: Record<string, 'secondary' | 'success' | 'warn' | 'danger'> = {
    rascunho: 'secondary',
    ativo: 'success',
    encerrado: 'warn',
};

const statusLabel: Record<string, string> = {
    rascunho: 'Rascunho',
    ativo: 'Aberto',
    encerrado: 'Encerrado',
};

function formatDate(dateStr: string | null): string {
    if (!dateStr) {
        return '—';
    }

    return new Date(dateStr).toLocaleDateString('pt-BR');
}

function isInscricaoAberta(process: { inscricao_inicio_em: string | null; inscricao_fim_em: string | null }): boolean {
    const now = new Date();
    const inicio = process.inscricao_inicio_em ? new Date(process.inscricao_inicio_em) : null;
    const fim = process.inscricao_fim_em ? new Date(process.inscricao_fim_em) : null;

    if (inicio && now < inicio) {
        return false;
    }

    if (fim && now > fim) {
        return false;
    }

    return true;
}

const startApplication = (processId: number): void => {
    router.post(start(processId).url);
};
</script>

<template>
    <div class="p-1">
        <Head title="Processos seletivos" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    title="Processos seletivos"
                    description="Consulte e inscreva-se nos processos seletivos com inscrições abertas."
                    :icon="FileText"
                />
            </div>

            <div
                v-if="processes.data.length === 0"
                class="flex flex-col items-center justify-center gap-4 rounded-xl border border-border bg-card py-16 text-center"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-muted">
                    <i class="pi pi-inbox text-2xl text-muted-foreground" />
                </div>
                <div>
                    <p class="text-base font-semibold">Nenhum processo disponível</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Não há processos seletivos abertos no momento. Volte mais tarde.
                    </p>
                </div>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="process in processes.data"
                    :key="process.id"
                    class="group flex flex-col rounded-xl shadow-sm transition-shadow hover:shadow-md"
                >
                    <template #header>
                        <div class="flex items-start justify-between gap-3 px-5 pt-5">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <BookOpen :size="18" />
                            </div>
                            <Tag
                                :value="statusLabel[process.status] ?? process.status"
                                :severity="statusSeverity[process.status] ?? 'secondary'"
                                class="shrink-0"
                            />
                        </div>
                    </template>
                    <template #title>
                        <span class="line-clamp-2 leading-snug">{{ process.titulo }}</span>
                    </template>
                    <template #subtitle>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <span v-if="process.orgao" class="flex items-center gap-1">
                                <Building2 :size="12" />
                                {{ process.orgao }}
                            </span>
                            <span v-if="process.area" class="flex items-center gap-1">
                                <i class="pi pi-tag text-[10px]" />
                                {{ process.area }}
                            </span>
                        </div>
                    </template>
                    <template #content>
                        <p class="line-clamp-3 text-sm text-muted-foreground">
                            {{ process.descricao || 'Sem descrição.' }}
                        </p>

                        <Divider class="my-3" />

                        <div class="flex flex-col gap-1 text-xs text-muted-foreground">
                            <div class="flex items-center gap-1.5">
                                <Calendar :size="12" />
                                <span>
                                    Inscrições: {{ formatDate(process.inscricao_inicio_em) }} até
                                    {{ formatDate(process.inscricao_fim_em) }}
                                </span>
                            </div>
                        </div>
                    </template>
                    <template #footer>
                        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <Link :href="show(process.id).url" class="min-w-0 flex-1 sm:min-w-[8.5rem]">
                                <Button
                                    label="Ver detalhes"
                                    icon="pi pi-eye"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    class="w-full"
                                    type="button"
                                />
                            </Link>
                            <a
                                v-if="process.edital_download_url"
                                :href="process.edital_download_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="min-w-0 flex-1 sm:min-w-[8.5rem]"
                            >
                                <Button
                                    label="Edital"
                                    icon="pi pi-download"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    class="w-full"
                                    type="button"
                                />
                            </a>
                            <Button
                                v-if="process.status === 'ativo' && isInscricaoAberta(process)"
                                label="Inscrever-se"
                                icon="pi pi-send"
                                size="small"
                                class="w-full flex-1 sm:min-w-[8.5rem]"
                                type="button"
                                @click="startApplication(process.id)"
                            />
                        </div>
                    </template>
                </Card>
            </div>

            <div
                v-if="processes.last_page > 1"
                class="flex items-center justify-center gap-2 py-2"
            >
                <Button
                    icon="pi pi-chevron-left"
                    severity="secondary"
                    text
                    rounded
                    :disabled="processes.current_page === 1"
                    @click="router.get(processesIndex().url, { page: processes.current_page - 1 }, { preserveState: true })"
                />
                <span class="text-sm text-muted-foreground">
                    Página {{ processes.current_page }} de {{ processes.last_page }}
                </span>
                <Button
                    icon="pi pi-chevron-right"
                    severity="secondary"
                    text
                    rounded
                    :disabled="processes.current_page === processes.last_page"
                    @click="router.get(processesIndex().url, { page: processes.current_page + 1 }, { preserveState: true })"
                />
            </div>
        </div>
    </div>
</template>
