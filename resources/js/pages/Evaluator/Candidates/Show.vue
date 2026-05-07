<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ClipboardCheck } from 'lucide-vue-next';
import Button from 'primevue/button';
import ButtonGroup from 'primevue/buttongroup';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Fluid from 'primevue/fluid';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Heading from '@/components/Heading.vue';
import evaluatorDocuments from '@/routes/evaluator/candidates/documents';
import scoreRoutes from '@/routes/evaluator/candidates/score';

const props = defineProps<{
    application: {
        id: number;
        status: string;
        documents?: Array<{
            id: number;
            nome_arquivo: string;
            status: string;
            motivo_recusa?: string | null;
        }>;
        selection_process?: {
            criteria?: Array<{
                id: number;
                nome: string;
                pontuacao_max: number;
            }>;
        };
    };
}>();

const decisionForm = useForm({
    status: 'aprovado',
    motivo_recusa: '',
});

const scoreForm = useForm({
    scores: (props.application.selection_process?.criteria ?? []).map(
        (item) => ({
            process_criteria_id: item.id,
            pontuacao: 0,
        }),
    ),
    resultado: 'classificado',
    observacoes: '',
});

const decisionOptions = [
    { label: 'Aprovado', value: 'aprovado' },
    { label: 'Recusado', value: 'recusado' },
];

const resultOptions = [
    { label: 'Classificado', value: 'classificado' },
    { label: 'Desclassificado', value: 'desclassificado' },
    { label: 'Apto', value: 'apto' },
    { label: 'Inapto', value: 'inapto' },
    { label: 'Suplente', value: 'suplente' },
];

const decideDocument = (documentId: number): void => {
    decisionForm.post(
        evaluatorDocuments.decision({
            application: props.application.id,
            applicationDocument: documentId,
        }).url,
    );
};

const saveScore = (): void => {
    scoreForm.post(scoreRoutes.store(props.application.id).url);
};
</script>

<template>
    <div class="p-3 md:p-6">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
            <Heading
                title="Avaliação de Candidato"
                description="Valide documentos e registre pontuação por critério."
                :icon="ClipboardCheck"
            />

            <Card class="rounded-xl shadow-md">
                <template #title>Avaliação de Candidato</template>
                <template #content>
                    <p class="text-sm text-gray-600">
                        Inscrição #{{ application.id }} - {{ application.status }}
                    </p>
                </template>
            </Card>

            <Card class="rounded-xl shadow-md">
                <template #title>Validação de documentos</template>
                <template #content>
                    <DataTable :value="application.documents ?? []" striped-rows>
                        <Column
                            field="nome_arquivo"
                            header="Documento"
                            header-class="px-4 py-3"
                            body-class="px-4 py-3"
                        />
                        <Column
                            field="status"
                            header="Status"
                            header-class="px-4 py-3"
                            body-class="px-4 py-3"
                        />
                        <Column
                            header="Análise"
                            header-class="px-4 py-3"
                            body-class="px-4 py-3"
                        >
                            <template #body="{ data }">
                                <Fluid>
                                    <div class="flex flex-col gap-3">
                                        <Select
                                            v-model="decisionForm.status"
                                            :options="decisionOptions"
                                            option-label="label"
                                            option-value="value"
                                        />
                                        <Textarea
                                            v-model="decisionForm.motivo_recusa"
                                            rows="2"
                                            placeholder="Motivo da recusa (obrigatório ao recusar)"
                                        />
                                        <ButtonGroup>
                                            <Button
                                                size="small"
                                                label="Salvar decisão"
                                                @click="decideDocument(data.id)"
                                            />
                                        </ButtonGroup>
                                    </div>
                                </Fluid>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <Card class="rounded-xl shadow-md">
                <template #title>Pontuação por critério</template>
                <template #content>
                    <Fluid>
                        <div class="flex flex-col gap-4">
                            <div
                                v-for="(criteria, index) in application
                                    .selection_process?.criteria ?? []"
                                :key="criteria.id"
                                class="flex flex-col gap-2 rounded-xl border border-surface-200 p-4"
                            >
                                <p class="text-sm font-medium">{{ criteria.nome }}</p>
                                <InputNumber
                                    v-model="scoreForm.scores[index].pontuacao"
                                    :min="0"
                                    :max="criteria.pontuacao_max"
                                    :max-fraction-digits="2"
                                />
                            </div>

                            <Select
                                v-model="scoreForm.resultado"
                                :options="resultOptions"
                                option-label="label"
                                option-value="value"
                            />
                            <Textarea
                                v-model="scoreForm.observacoes"
                                rows="3"
                                placeholder="Observações do parecer"
                            />
                            <div class="flex justify-end">
                                <Button
                                    label="Salvar pontuação e parecer"
                                    icon="pi pi-check"
                                    @click="saveScore"
                                />
                            </div>
                        </div>
                    </Fluid>
                </template>
            </Card>
        </div>
    </div>
</template>
