<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ClipboardCheck } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Fluid from 'primevue/fluid';
import InputText from 'primevue/inputtext';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Textarea from 'primevue/textarea';
import Heading from '@/components/Heading.vue';
import candidateApplications from '@/routes/candidate/applications';
import step from '@/routes/candidate/applications/step';
import candidateDocuments from '@/routes/candidate/documents';

const props = defineProps<{
    application: {
        id: number;
        status: string;
        numero_protocolo: string | null;
        selection_process_id: number;
        documents?: Array<{ id: number; nome_arquivo: string; status: string }>;
    };
}>();

const stepOneForm = useForm({ payload: { nome: '', cpf: '' } });
const stepTwoForm = useForm({ payload: { formacao: '', experiencia: '' } });
const uploadForm = useForm({
    process_required_document_id: '' as string,
    arquivo: null as File | null,
});

const saveStep = (stepNumber: number): void => {
    const payload = stepNumber === 1 ? stepOneForm : stepTwoForm;
    payload.post(
        step.store({ application: props.application.id, step: stepNumber }).url,
    );
};

const submitApplication = (): void => {
    stepTwoForm.post(candidateApplications.submit(props.application.id).url);
};

const uploadDocument = (): void => {
    uploadForm.transform((data) => ({
        ...data,
        process_required_document_id: Number(data.process_required_document_id),
    }));
    uploadForm.post(candidateDocuments.store(props.application.id).url);
};
</script>

<template>
    <div class="p-3 md:p-6">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
            <Heading
                title="Detalhe da inscrição"
                description="Gerencie etapas, documentos e finalização da inscrição."
                :icon="ClipboardCheck"
            />

            <Card class="rounded-xl shadow-md">
                <template #title>Detalhe da inscrição</template>
                <template #content>
                    <div class="flex flex-col gap-2">
                        <p class="text-sm text-gray-600">
                            Protocolo:
                            {{ application.numero_protocolo ?? 'Pendente' }}
                        </p>
                        <p class="text-sm text-gray-600">
                            Status: {{ application.status }}
                        </p>
                    </div>
                </template>
            </Card>

            <Card class="rounded-xl shadow-md">
                <template #content>
                    <Tabs value="1">
                        <TabList>
                            <Tab value="1">Dados pessoais</Tab>
                            <Tab value="2">Informações específicas</Tab>
                            <Tab value="3">Documentos</Tab>
                            <Tab value="4">Revisão</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="1">
                                <Fluid>
                                    <div class="flex flex-col gap-4">
                                        <InputText
                                            v-model="stepOneForm.payload.nome"
                                            placeholder="Nome completo"
                                        />
                                        <InputText
                                            v-model="stepOneForm.payload.cpf"
                                            placeholder="CPF"
                                        />
                                        <div class="flex justify-end">
                                            <Button
                                                :fluid="false"
                                                label="Salvar etapa 1"
                                                size="small"
                                                @click="saveStep(1)"
                                            />
                                        </div>
                                    </div>
                                </Fluid>
                            </TabPanel>
                            <TabPanel value="2">
                                <Fluid>
                                    <div class="flex flex-col gap-4">
                                        <Textarea
                                            v-model="stepTwoForm.payload.formacao"
                                            placeholder="Formação"
                                            rows="3"
                                        />
                                        <Textarea
                                            v-model="
                                                stepTwoForm.payload.experiencia
                                            "
                                            placeholder="Experiência"
                                            rows="3"
                                        />
                                        <div class="flex justify-end">
                                            <Button
                                                :fluid="false"
                                                label="Salvar etapa 2"
                                                size="small"
                                                @click="saveStep(2)"
                                            />
                                        </div>
                                    </div>
                                </Fluid>
                            </TabPanel>
                            <TabPanel value="3">
                                <Fluid>
                                    <div class="flex flex-col gap-4">
                                        <InputText
                                            v-model="
                                                uploadForm.process_required_document_id
                                            "
                                            placeholder="ID do documento obrigatório"
                                        />
                                        <input
                                            type="file"
                                            @change="
                                                uploadForm.arquivo =
                                                    ($event.target as HTMLInputElement)
                                                        .files?.[0] ?? null
                                            "
                                        />
                                        <div class="flex justify-end">
                                            <Button
                                                :fluid="false"
                                                label="Enviar documento"
                                                size="small"
                                                @click="uploadDocument"
                                            />
                                        </div>
                                    </div>
                                </Fluid>
                            </TabPanel>
                            <TabPanel value="4">
                                <div class="flex flex-col gap-4">
                                    <p class="text-sm text-gray-700">
                                        Revise os dados e finalize sua inscrição.
                                    </p>
                                    <div class="flex justify-end">
                                        <Button
                                            :fluid="false"
                                            label="Finalizar inscrição"
                                            icon="pi pi-check"
                                            size="small"
                                            @click="submitApplication"
                                        />
                                    </div>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </template>
            </Card>

            <Card class="rounded-xl shadow-md">
                <template #title>Documentos enviados</template>
                <template #content>
                    <ul class="flex flex-col gap-2 text-sm text-gray-700">
                        <li v-for="doc in application.documents ?? []" :key="doc.id">
                            {{ doc.nome_arquivo }} - {{ doc.status }}
                        </li>
                    </ul>
                </template>
            </Card>
        </div>
    </div>
</template>
