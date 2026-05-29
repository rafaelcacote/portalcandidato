<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Gavel, ScrollText } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { store as storeAppeal } from '@/routes/candidate/applications/appeals';

export type ProfessionalDocumentRow = {
    key: string;
    label: string;
    description: string;
    download_url: string;
    print_url: string;
    kind: string;
    stage_id: number | null;
};

export type AppealStageRow = {
    id: number;
    nome: string;
    ordem: number;
    recurso_aberto: boolean;
    recurso_inicio_em: string | null;
    recurso_fim_em: string | null;
};

export type AppealRow = {
    id: number;
    texto: string;
    status: string;
    status_label: string;
    resposta: string | null;
    respondido_em: string | null;
    created_at: string;
    stage: { id: number; nome: string } | null;
};

const props = defineProps<{
    applicationId: number;
    isFinalized: boolean;
    professionalDocuments: ProfessionalDocumentRow[];
    appealStages: AppealStageRow[];
    appeals: AppealRow[];
    hasOpenRecursoWindow: boolean;
}>();

const showAppealForm = ref(false);

const appealForm = useForm({
    process_stage_id: null as number | null,
    texto: '',
});

const openAppealStages = computed(() =>
    props.appealStages.filter((stage) => stage.recurso_aberto),
);

const stageOptions = computed(() =>
    openAppealStages.value.map((stage) => ({
        label: `Etapa ${stage.ordem} — ${stage.nome}`,
        value: stage.id,
    })),
);

const submittedStageIds = computed(
    () => new Set(props.appeals.map((a) => a.stage?.id).filter((id): id is number => id != null)),
);

const availableAppealStages = computed(() =>
    openAppealStages.value.filter((stage) => !submittedStageIds.value.has(stage.id)),
);

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function openPrint(url: string): void {
    window.open(url, '_blank', 'noopener,noreferrer');
}

function submitAppeal(): void {
    appealForm.post(storeAppeal.url({ application: props.applicationId }), {
        preserveScroll: true,
        onSuccess: () => {
            appealForm.reset();
            showAppealForm.value = false;
        },
    });
}

const appealStatusSeverity: Record<string, 'secondary' | 'success' | 'warn' | 'danger'> = {
    enviado: 'secondary',
    em_analise: 'warn',
    deferido: 'success',
    indeferido: 'danger',
};
</script>

<template>
    <div v-if="isFinalized" class="flex flex-col gap-5">
        <!-- Documentos profissionais -->
        <Card class="rounded-xl border-border/80 shadow-sm">
            <template #title>
                <div class="flex items-center gap-2">
                    <ScrollText :size="18" class="text-primary" aria-hidden="true" />
                    Documentos para fins profissionais
                </div>
            </template>
            <template #subtitle>
                Emita comprovantes e declarações em PDF para apresentação a empregadores, conselhos e
                instituições.
            </template>
            <template #content>
                <div v-if="professionalDocuments.length === 0" class="text-sm text-muted-foreground">
                    Nenhum documento disponível no momento.
                </div>
                <ul v-else class="flex flex-col gap-3">
                    <li
                        v-for="doc in professionalDocuments"
                        :key="doc.key"
                        class="flex flex-col gap-3 rounded-xl border border-border/70 bg-muted/10 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <p class="font-semibold text-foreground">{{ doc.label }}</p>
                            <p class="mt-1 text-sm text-muted-foreground">{{ doc.description }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <a :href="doc.download_url" target="_blank" rel="noopener noreferrer">
                                <Button
                                    label="Baixar PDF"
                                    icon="pi pi-download"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                />
                            </a>
                            <Button
                                label="Imprimir"
                                icon="pi pi-print"
                                size="small"
                                severity="secondary"
                                outlined
                                @click="openPrint(doc.print_url)"
                            />
                        </div>
                    </li>
                </ul>
            </template>
        </Card>

        <!-- Recursos -->
        <Card class="rounded-xl border-border/80 shadow-sm">
            <template #title>
                <div class="flex items-center gap-2">
                    <Gavel :size="18" class="text-primary" aria-hidden="true" />
                    Recursos
                </div>
            </template>
            <template #subtitle>
                Solicite revisão de decisões dentro do prazo previsto para cada etapa do processo.
            </template>
            <template #content>
                <Message v-if="!hasOpenRecursoWindow" severity="info" :closable="false" class="mb-4">
                    Não há prazo de recurso aberto no momento. Quando uma etapa encerrar e o prazo for
                    configurado, você poderá enviar seu recurso aqui.
                </Message>

                <div v-if="appeals.length" class="mb-4 space-y-2">
                    <p class="text-sm font-semibold text-foreground">Recursos enviados</p>
                    <ul class="flex flex-col gap-2">
                        <li
                            v-for="appeal in appeals"
                            :key="appeal.id"
                            class="rounded-lg border border-border/70 px-4 py-3 text-sm"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium">
                                    {{ appeal.stage?.nome ?? 'Etapa não informada' }}
                                </span>
                                <Tag
                                    :value="appeal.status_label"
                                    :severity="appealStatusSeverity[appeal.status] ?? 'secondary'"
                                />
                            </div>
                            <p class="mt-2 whitespace-pre-line text-muted-foreground">{{ appeal.texto }}</p>
                            <div
                                v-if="appeal.resposta"
                                class="mt-3 rounded-lg border border-primary/20 bg-primary/5 px-3 py-2"
                            >
                                <p class="text-xs font-semibold text-primary">Resposta da comissão</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-foreground">
                                    {{ appeal.resposta }}
                                </p>
                                <p
                                    v-if="appeal.respondido_em"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ formatDate(appeal.respondido_em) }}
                                </p>
                            </div>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Enviado em {{ formatDate(appeal.created_at) }}
                            </p>
                        </li>
                    </ul>
                </div>

                <div
                    v-if="availableAppealStages.length > 0"
                    class="flex flex-col gap-4"
                >
                    <Button
                        v-if="!showAppealForm"
                        label="Enviar novo recurso"
                        icon="pi pi-plus"
                        size="small"
                        @click="showAppealForm = true"
                    />

                    <form
                        v-else
                        class="space-y-4 rounded-xl border border-border bg-muted/10 p-4"
                        @submit.prevent="submitAppeal"
                    >
                        <div class="grid gap-2">
                            <Label for="appeal-stage">Etapa do processo *</Label>
                            <Dropdown
                                id="appeal-stage"
                                v-model="appealForm.process_stage_id"
                                :options="stageOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Selecione a etapa"
                                class="w-full"
                            />
                            <InputError :message="appealForm.errors.process_stage_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="appeal-texto">Fundamentação do recurso *</Label>
                            <Textarea
                                id="appeal-texto"
                                v-model="appealForm.texto"
                                rows="5"
                                class="w-full"
                                placeholder="Descreva de forma clara o motivo do recurso e os argumentos que sustentam seu pedido."
                            />
                            <InputError :message="appealForm.errors.texto" />
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                label="Enviar recurso"
                                icon="pi pi-send"
                                size="small"
                                :loading="appealForm.processing"
                            />
                            <Button
                                type="button"
                                label="Cancelar"
                                severity="secondary"
                                outlined
                                size="small"
                                :disabled="appealForm.processing"
                                @click="showAppealForm = false"
                            />
                        </div>
                    </form>
                </div>

                <ul v-if="appealStages.length" class="mt-4 space-y-2 border-t border-border pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Prazos por etapa
                    </p>
                    <li
                        v-for="stage in appealStages"
                        :key="stage.id"
                        class="flex flex-wrap items-center justify-between gap-2 text-sm"
                    >
                        <span>{{ stage.ordem }}. {{ stage.nome }}</span>
                        <Tag
                            :value="stage.recurso_aberto ? 'Recurso aberto' : 'Recurso encerrado'"
                            :severity="stage.recurso_aberto ? 'success' : 'secondary'"
                        />
                    </li>
                </ul>
            </template>
        </Card>
    </div>
</template>
