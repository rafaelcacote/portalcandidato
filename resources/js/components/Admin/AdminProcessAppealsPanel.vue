<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Gavel } from 'lucide-vue-next';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { formatDateTimeBR } from '@/lib/utils';
import { show as applicationShow } from '@/routes/candidate/applications';
import { update as updateAppeal } from '@/routes/admin/processes/appeals';

export type ProcessAppealRow = {
    id: number;
    texto: string;
    status: string;
    status_label: string;
    resposta: string | null;
    created_at: string | null;
    respondido_em: string | null;
    application: {
        id: number;
        numero_protocolo: string | null;
        user_name: string | null;
        user_email: string | null;
    };
    stage: { id: number; nome: string; ordem: number } | null;
    respondido_por: { name: string } | null;
};

export type AppealStatusOption = {
    value: string;
    label: string;
};

const props = defineProps<{
    selectionProcessId: number;
    appeals: ProcessAppealRow[];
    appealStatusOptions: AppealStatusOption[];
}>();

const respondDialogOpen = ref(false);
const selectedAppeal = ref<ProcessAppealRow | null>(null);

const respondForm = useForm({
    status: 'em_analise',
    resposta: '',
});

const statusSeverity: Record<string, 'secondary' | 'success' | 'warn' | 'danger'> = {
    enviado: 'secondary',
    em_analise: 'warn',
    deferido: 'success',
    indeferido: 'danger',
};

function formatDate(iso: string | null | undefined): string {
    if (!iso) return '—';
    return formatDateTimeBR(iso, { dateStyle: 'short', timeStyle: 'short' });
}

function openRespond(appeal: ProcessAppealRow): void {
    selectedAppeal.value = appeal;
    respondForm.status = appeal.status === 'enviado' ? 'em_analise' : appeal.status;
    respondForm.resposta = appeal.resposta ?? '';
    respondDialogOpen.value = true;
}

function submitResponse(): void {
    if (!selectedAppeal.value) return;

    respondForm.put(
        updateAppeal({
            selectionProcess: props.selectionProcessId,
            applicationAppeal: selectedAppeal.value.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                respondDialogOpen.value = false;
                selectedAppeal.value = null;
                respondForm.reset();
            },
        },
    );
}
</script>

<template>
    <div class="flex flex-col gap-5">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-700"
                >
                    <Gavel :size="20" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold">Recursos dos candidatos</h2>
                    <p class="text-sm text-muted-foreground">
                        Analise os recursos enviados e registre a decisão com resposta ao candidato.
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="appeals.length === 0"
            class="flex flex-col items-center justify-center gap-2 rounded-xl border py-12 text-center"
        >
            <i class="pi pi-inbox text-3xl text-muted-foreground" />
            <p class="text-sm font-medium">Nenhum recurso recebido</p>
            <p class="max-w-sm text-xs text-muted-foreground">
                Quando candidatos enviarem recursos nas etapas com prazo aberto, eles aparecerão aqui.
            </p>
        </div>

        <div v-else class="overflow-hidden rounded-xl border">
            <div
                v-for="(appeal, index) in appeals"
                :key="appeal.id"
                class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-start sm:justify-between"
                :class="index > 0 ? 'border-t' : ''"
            >
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold text-foreground">
                            {{ appeal.application.user_name ?? 'Candidato' }}
                        </span>
                        <Tag
                            :value="appeal.status_label"
                            :severity="statusSeverity[appeal.status] ?? 'secondary'"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ appeal.application.user_email }}
                        <span v-if="appeal.application.numero_protocolo">
                            · Protocolo {{ appeal.application.numero_protocolo }}
                        </span>
                        <span v-if="appeal.stage"> · Etapa: {{ appeal.stage.nome }}</span>
                    </p>
                    <p class="whitespace-pre-line text-sm text-foreground">{{ appeal.texto }}</p>
                    <Message
                        v-if="appeal.resposta"
                        severity="info"
                        :closable="false"
                        class="text-sm"
                    >
                        <p class="font-medium">Resposta da comissão</p>
                        <p class="mt-1 whitespace-pre-line">{{ appeal.resposta }}</p>
                        <p v-if="appeal.respondido_em" class="mt-2 text-xs opacity-80">
                            Respondido em {{ formatDate(appeal.respondido_em) }}
                            <span v-if="appeal.respondido_por">
                                por {{ appeal.respondido_por.name }}
                            </span>
                        </p>
                    </Message>
                    <p class="text-xs text-muted-foreground">
                        Enviado em {{ formatDate(appeal.created_at) }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <Link
                        :href="applicationShow({ application: appeal.application.id }).url"
                        class="inline-flex"
                    >
                        <Button
                            label="Ver inscrição"
                            icon="pi pi-external-link"
                            size="small"
                            severity="secondary"
                            outlined
                        />
                    </Link>
                    <Button
                        label="Analisar"
                        icon="pi pi-pencil"
                        size="small"
                        @click="openRespond(appeal)"
                    />
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="respondDialogOpen"
            modal
            header="Análise de recurso"
            :style="{ width: 'min(100%, 32rem)' }"
            :draggable="false"
        >
            <form v-if="selectedAppeal" class="space-y-4" @submit.prevent="submitResponse">
                <p class="text-sm text-muted-foreground">
                    Candidato:
                    <strong class="text-foreground">{{ selectedAppeal.application.user_name }}</strong>
                    <span v-if="selectedAppeal.stage">
                        — {{ selectedAppeal.stage.nome }}
                    </span>
                </p>

                <div class="grid gap-2">
                    <Label for="appeal-status">Status *</Label>
                    <Dropdown
                        id="appeal-status"
                        v-model="respondForm.status"
                        :options="appealStatusOptions"
                        option-label="label"
                        option-value="value"
                        class="w-full"
                    />
                    <InputError :message="respondForm.errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="appeal-resposta">
                        Resposta ao candidato
                        <span
                            v-if="
                                respondForm.status === 'deferido' ||
                                respondForm.status === 'indeferido'
                            "
                            class="text-destructive"
                        >
                            *
                        </span>
                    </Label>
                    <Textarea
                        id="appeal-resposta"
                        v-model="respondForm.resposta"
                        rows="5"
                        class="w-full"
                        placeholder="Fundamentação da decisão que será exibida ao candidato."
                    />
                    <InputError :message="respondForm.errors.resposta" />
                </div>

                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        label="Cancelar"
                        severity="secondary"
                        outlined
                        :disabled="respondForm.processing"
                        @click="respondDialogOpen = false"
                    />
                    <Button
                        type="submit"
                        label="Salvar análise"
                        icon="pi pi-check"
                        :loading="respondForm.processing"
                    />
                </div>
            </form>
        </Dialog>
    </div>
</template>
