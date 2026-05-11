<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    BookOpen,
    Calendar,
    CheckCircle2,
    ClipboardList,
    FileText,
    ListChecks,
    ShieldCheck,
    Star,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Timeline from 'primevue/timeline';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { edit as profileEdit } from '@/routes/profile';
import { home } from '@/routes';
import { start as startApplication } from '@/routes/candidate/applications';
import { index, show } from '@/routes/candidate/processes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Processos seletivos', href: index().url },
            { title: 'Detalhes', href: '#' },
        ],
    },
});

const props = defineProps<{
    selectionProcess: {
        id: number;
        titulo: string;
        descricao: string;
        regras?: string | null;
        status: string;
        orgao?: string | null;
        area?: string | null;
        inscricao_inicio_em: string | null;
        inscricao_fim_em: string | null;
        stages?: Array<{
            id: number;
            nome: string;
            ordem: number;
            descricao?: string | null;
            data_inicio?: string | null;
            data_fim?: string | null;
        }>;
        tipo_programa?: string | null;
        edital_download_url?: string | null;
        required_documents?: Array<{
            id: number;
            nome: string;
            obrigatorio: boolean;
            descricao?: string | null;
        }>;
        criteria?: Array<{
            id: number;
            nome: string;
            pontos_maximos: number;
            descricao?: string | null;
        }>;
    };
    alreadyApplied?: boolean;
}>();

const statusSeverity: Record<string, 'secondary' | 'success' | 'warn' | 'danger'> = {
    rascunho: 'secondary',
    ativo: 'success',
    encerrado: 'warn',
};

const statusLabel: Record<string, string> = {
    rascunho: 'Rascunho',
    ativo: 'Inscrições abertas',
    encerrado: 'Encerrado',
};

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
}

function isInscricaoAberta(): boolean {
    const now = new Date();
    const inicio = props.selectionProcess.inscricao_inicio_em
        ? new Date(props.selectionProcess.inscricao_inicio_em)
        : null;
    const fim = props.selectionProcess.inscricao_fim_em
        ? new Date(props.selectionProcess.inscricao_fim_em)
        : null;
    if (inicio && now < inicio) return false;
    if (fim && now > fim) return false;
    return props.selectionProcess.status === 'ativo';
}

const page = usePage<{ candidateProfileComplete?: boolean }>();

const candidateProfileComplete = computed(
    () => page.props.candidateProfileComplete ?? true,
);

const doStartApplication = (): void => {
    router.post(startApplication(props.selectionProcess.id).url);
};

const timelineEvents = (
    props.selectionProcess.stages ?? []
).map((s) => ({
    status: s.nome,
    date: s.data_inicio ? formatDate(s.data_inicio) : null,
    icon: 'pi pi-check',
    color: 'var(--p-primary-color)',
    description: s.descricao,
    ordem: s.ordem,
}));
</script>

<template>
    <div class="p-1">
        <Head :title="selectionProcess.titulo" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-5">
            <!-- Cabeçalho -->
            <div class="flex items-start justify-between gap-8 py-3">
                <Heading
                    :title="selectionProcess.titulo"
                    description="Detalhes, etapas, critérios de pontuação e documentos exigidos."
                    :icon="FileText"
                />
                <Link :href="index().url">
                    <Button
                        label="Voltar"
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        outlined
                        size="small"
                    />
                </Link>
            </div>

            <!-- Banner de status e prazo -->
            <Message
                v-if="isInscricaoAberta() && !alreadyApplied && !candidateProfileComplete"
                severity="warn"
                :closable="false"
                class="rounded-xl"
            >
                <div class="flex flex-col gap-2 text-sm">
                    <span>
                        Para se inscrever, complete antes todos os dados do seu
                        perfil cadastral (dados pessoais, documento e endereço).
                    </span>
                    <Link :href="profileEdit().url">
                        <Button
                            label="Ir para meu perfil"
                            icon="pi pi-user"
                            size="small"
                            severity="warn"
                        />
                    </Link>
                </div>
            </Message>

            <div
                v-if="isInscricaoAberta()"
                class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-green-200 bg-green-50 px-5 py-4 dark:border-green-900 dark:bg-green-950/30"
            >
                <div class="flex items-center gap-3">
                    <CheckCircle2 :size="20" class="shrink-0 text-green-600 dark:text-green-400" />
                    <div>
                        <p class="font-semibold text-green-800 dark:text-green-300">
                            Inscrições abertas
                        </p>
                        <p class="text-sm text-green-700 dark:text-green-400">
                            Prazo: até {{ formatDate(selectionProcess.inscricao_fim_em) }}
                        </p>
                    </div>
                </div>
                <Button
                    v-if="!alreadyApplied && candidateProfileComplete"
                    label="Inscrever-se agora"
                    icon="pi pi-send"
                    size="small"
                    @click="doStartApplication"
                />
                <Tag
                    v-else
                    value="Já inscrito"
                    severity="success"
                    icon="pi pi-check"
                />
            </div>

            <div
                v-else-if="selectionProcess.status === 'encerrado'"
                class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-900 dark:bg-amber-950/30"
            >
                <AlertCircle :size="20" class="shrink-0 text-amber-600 dark:text-amber-400" />
                <div>
                    <p class="font-semibold text-amber-800 dark:text-amber-300">
                        Inscrições encerradas
                    </p>
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        O prazo de inscrições para este processo foi encerrado.
                    </p>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <!-- Coluna principal -->
                <div class="flex flex-col gap-5 lg:col-span-2">
                    <!-- Descrição e regras -->
                    <Card class="rounded-xl shadow-sm">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <BookOpen :size="16" class="text-muted-foreground" />
                                Sobre o processo
                            </div>
                        </template>
                        <template #content>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                {{ selectionProcess.descricao || 'Sem descrição cadastrada.' }}
                            </p>

                            <template v-if="selectionProcess.regras">
                                <Divider />
                                <div class="flex items-center gap-2 font-semibold text-foreground">
                                    <ShieldCheck :size="16" class="text-muted-foreground" />
                                    Regras
                                </div>
                                <p class="mt-2 text-sm leading-relaxed text-muted-foreground whitespace-pre-line">
                                    {{ selectionProcess.regras }}
                                </p>
                            </template>
                        </template>
                    </Card>

                    <!-- Etapas -->
                    <Card class="rounded-xl shadow-sm">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <ListChecks :size="16" class="text-muted-foreground" />
                                Etapas do processo
                            </div>
                        </template>
                        <template #content>
                            <div v-if="(selectionProcess.stages ?? []).length === 0" class="py-6 text-center text-sm text-muted-foreground">
                                Nenhuma etapa cadastrada.
                            </div>
                            <Timeline
                                v-else
                                :value="timelineEvents"
                                class="[&_.p-timeline-event-content]:pb-5"
                            >
                                <template #marker="{ item }">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground"
                                    >
                                        {{ item.ordem }}
                                    </div>
                                </template>
                                <template #content="{ item }">
                                    <div class="pb-4">
                                        <p class="font-semibold text-foreground">{{ item.status }}</p>
                                        <p v-if="item.date" class="mt-0.5 text-xs text-muted-foreground">
                                            <i class="pi pi-calendar mr-1" />{{ item.date }}
                                        </p>
                                        <p v-if="item.description" class="mt-1 text-sm text-muted-foreground">
                                            {{ item.description }}
                                        </p>
                                    </div>
                                </template>
                            </Timeline>
                        </template>
                    </Card>

                    <!-- Critérios de pontuação -->
                    <Card
                        v-if="(selectionProcess.criteria ?? []).length"
                        class="rounded-xl shadow-sm"
                    >
                        <template #title>
                            <div class="flex items-center gap-2">
                                <Star :size="16" class="text-muted-foreground" />
                                Critérios de pontuação
                            </div>
                        </template>
                        <template #content>
                            <div class="flex flex-col divide-y divide-border">
                                <div
                                    v-for="criterion in selectionProcess.criteria"
                                    :key="criterion.id"
                                    class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                >
                                    <div class="min-w-0">
                                        <p class="font-medium text-foreground">{{ criterion.nome }}</p>
                                        <p v-if="criterion.descricao" class="mt-0.5 text-sm text-muted-foreground">
                                            {{ criterion.descricao }}
                                        </p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <span class="text-lg font-bold text-primary">{{ criterion.pontos_maximos }}</span>
                                        <p class="text-xs text-muted-foreground">pts máx.</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Coluna lateral: info + documentos -->
                <div class="flex flex-col gap-5">
                    <!-- Informações gerais -->
                    <Card class="rounded-xl shadow-sm">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <ClipboardList :size="16" class="text-muted-foreground" />
                                Informações
                            </div>
                        </template>
                        <template #content>
                            <div class="flex flex-col gap-4">
                                <div>
                                    <p class="text-xs font-medium text-muted-foreground">Status</p>
                                    <Tag
                                        class="mt-1"
                                        :value="statusLabel[selectionProcess.status] ?? selectionProcess.status"
                                        :severity="statusSeverity[selectionProcess.status] ?? 'secondary'"
                                    />
                                </div>
                                <div v-if="selectionProcess.orgao">
                                    <p class="text-xs font-medium text-muted-foreground">Órgão</p>
                                    <p class="mt-0.5 text-sm font-medium text-foreground">{{ selectionProcess.orgao }}</p>
                                </div>
                                <div v-if="selectionProcess.area">
                                    <p class="text-xs font-medium text-muted-foreground">Área</p>
                                    <p class="mt-0.5 text-sm font-medium text-foreground">{{ selectionProcess.area }}</p>
                                </div>
                                <div v-if="selectionProcess.tipo_programa">
                                    <p class="text-xs font-medium text-muted-foreground">Programa</p>
                                    <p class="mt-0.5 text-sm font-medium text-foreground">
                                        {{
                                            selectionProcess.tipo_programa === 'doutorado'
                                                ? 'Doutorado'
                                                : 'Mestrado'
                                        }}
                                    </p>
                                </div>
                                <div v-if="selectionProcess.edital_download_url">
                                    <p class="text-xs font-medium text-muted-foreground">Edital</p>
                                    <a
                                        :href="selectionProcess.edital_download_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-2 inline-flex"
                                    >
                                        <Button
                                            label="Baixar edital (PDF)"
                                            icon="pi pi-download"
                                            size="small"
                                            outlined
                                            type="button"
                                        />
                                    </a>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-muted-foreground">Início das inscrições</p>
                                    <p class="mt-0.5 flex items-center gap-1.5 text-sm font-medium text-foreground">
                                        <Calendar :size="12" />
                                        {{ formatDate(selectionProcess.inscricao_inicio_em) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-muted-foreground">Prazo final</p>
                                    <p class="mt-0.5 flex items-center gap-1.5 text-sm font-medium text-foreground">
                                        <Calendar :size="12" />
                                        {{ formatDate(selectionProcess.inscricao_fim_em) }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Documentos exigidos -->
                    <Card class="rounded-xl shadow-sm">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <FileText :size="16" class="text-muted-foreground" />
                                Documentos exigidos
                            </div>
                        </template>
                        <template #content>
                            <div v-if="!(selectionProcess.required_documents ?? []).length" class="py-4 text-center text-sm text-muted-foreground">
                                Nenhum documento exigido.
                            </div>
                            <ul v-else class="flex flex-col gap-2">
                                <li
                                    v-for="doc in selectionProcess.required_documents"
                                    :key="doc.id"
                                    class="flex items-start gap-2 rounded-lg border border-border p-3"
                                >
                                    <i
                                        :class="doc.obrigatorio ? 'pi pi-file text-primary' : 'pi pi-file text-muted-foreground'"
                                        class="mt-0.5 shrink-0 text-sm"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground">{{ doc.nome }}</p>
                                        <p v-if="doc.descricao" class="mt-0.5 text-xs text-muted-foreground">{{ doc.descricao }}</p>
                                        <Tag
                                            v-if="doc.obrigatorio"
                                            value="Obrigatório"
                                            severity="danger"
                                            class="mt-1 text-xs"
                                        />
                                        <Tag
                                            v-else
                                            value="Opcional"
                                            severity="secondary"
                                            class="mt-1 text-xs"
                                        />
                                    </div>
                                </li>
                            </ul>
                        </template>
                    </Card>

                    <!-- CTA mobile -->
                    <div v-if="isInscricaoAberta() && !alreadyApplied && candidateProfileComplete" class="lg:hidden">
                        <Button
                            label="Inscrever-se neste processo"
                            icon="pi pi-send"
                            class="w-full"
                            @click="doStartApplication"
                        />
                    </div>
                    <div v-if="isInscricaoAberta() && !alreadyApplied && candidateProfileComplete" class="hidden lg:block">
                        <Button
                            label="Inscrever-se neste processo"
                            icon="pi pi-send"
                            class="w-full"
                            @click="doStartApplication"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
