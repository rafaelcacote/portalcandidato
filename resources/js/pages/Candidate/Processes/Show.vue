<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Heading from '@/components/Heading.vue';
import { start } from '@/routes/candidate/applications';

const props = defineProps<{
    selectionProcess: {
        id: number;
        titulo: string;
        descricao: string;
        regras?: string | null;
        stages?: Array<{ id: number; nome: string; ordem: number }>;
        required_documents?: Array<{
            id: number;
            nome: string;
            obrigatorio: boolean;
        }>;
    };
}>();

const startApplication = (): void => {
    router.post(start(props.selectionProcess.id).url);
};
</script>

<template>
    <div class="space-y-4">
        <Heading
            :title="selectionProcess.titulo"
            description="Detalhes do processo seletivo e próximas etapas."
            :icon="FileText"
        />

        <Card>
            <template #title>{{ selectionProcess.titulo }}</template>
            <template #content>
                <p class="mb-3 text-sm text-gray-700">
                    {{ selectionProcess.descricao }}
                </p>
                <p class="text-sm text-gray-600">
                    {{
                        selectionProcess.regras ||
                        'Sem regras adicionais cadastradas.'
                    }}
                </p>
            </template>
        </Card>

        <Card>
            <template #title>Próximas etapas</template>
            <template #content>
                <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700">
                    <li
                        v-for="stage in selectionProcess.stages ?? []"
                        :key="stage.id"
                    >
                        {{ stage.ordem }} - {{ stage.nome }}
                    </li>
                </ul>
            </template>
        </Card>

        <div>
            <Button
                label="Inscrever-se neste processo"
                icon="pi pi-send"
                @click="startApplication"
            />
        </div>
    </div>
</template>
