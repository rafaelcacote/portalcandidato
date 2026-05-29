<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import { ref } from 'vue';
import { formatDateTimeBR, toDatetimeLocalInputValue } from '@/lib/utils';
import { update as updateStage } from '@/routes/admin/processes/stages';

export type StageRow = {
    id: number;
    nome: string;
    ordem: number;
    inicio_em?: string | null;
    fim_em?: string | null;
    recurso_inicio_em?: string | null;
    recurso_fim_em?: string | null;
};

const props = defineProps<{
    selectionProcessId: number;
}>();

const dialogOpen = ref(false);
const editingStage = ref<StageRow | null>(null);

const stageForm = useForm({
    recurso_inicio_em: '',
    recurso_fim_em: '',
});

function formatPeriod(inicio?: string | null, fim?: string | null): string | null {
    if (!inicio && !fim) return null;
    const options = { dateStyle: 'short' as const, timeStyle: 'short' as const };
    if (inicio && fim) {
        return `${formatDateTimeBR(inicio, options)} — ${formatDateTimeBR(fim, options)}`;
    }
    if (inicio) return `A partir de ${formatDateTimeBR(inicio, options)}`;
    return `Até ${formatDateTimeBR(fim, options)}`;
}

function openEditor(stage: StageRow): void {
    editingStage.value = stage;
    stageForm.recurso_inicio_em = toDatetimeLocalInputValue(stage.recurso_inicio_em);
    stageForm.recurso_fim_em = toDatetimeLocalInputValue(stage.recurso_fim_em);
    dialogOpen.value = true;
}

function submit(): void {
    if (!editingStage.value) return;

    stageForm.put(
        updateStage({
            selectionProcess: props.selectionProcessId,
            processStage: editingStage.value.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
                editingStage.value = null;
            },
        },
    );
}

defineExpose({ formatPeriod, openEditor });
</script>

<template>
    <Dialog
        v-model:visible="dialogOpen"
        modal
        :header="`Prazo de recurso — ${editingStage?.nome ?? ''}`"
        :style="{ width: 'min(100%, 28rem)' }"
        :draggable="false"
    >
        <form v-if="editingStage" class="space-y-4" @submit.prevent="submit">
            <p class="text-sm text-muted-foreground">
                Se não informar as datas, o sistema usa automaticamente até 5 dias após o fim da
                etapa (<span v-if="editingStage.fim_em">{{ formatPeriod(null, editingStage.fim_em) }}</span
                ><span v-else>defina o fim da etapa</span>).
            </p>
            <label class="flex flex-col gap-1.5">
                <span class="text-sm">Início do prazo de recurso (opcional)</span>
                <InputText v-model="stageForm.recurso_inicio_em" type="datetime-local" />
            </label>
            <label class="flex flex-col gap-1.5">
                <span class="text-sm">Fim do prazo de recurso (opcional)</span>
                <InputText v-model="stageForm.recurso_fim_em" type="datetime-local" />
            </label>
            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    label="Cancelar"
                    severity="secondary"
                    outlined
                    @click="dialogOpen = false"
                />
                <Button
                    type="submit"
                    label="Salvar prazos"
                    icon="pi pi-check"
                    :loading="stageForm.processing"
                />
            </div>
        </form>
    </Dialog>
</template>
