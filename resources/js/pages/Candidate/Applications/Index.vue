<script setup lang="ts">
import { ClipboardCheck } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';

defineProps<{
    applications: {
        data: Array<{
            id: number;
            status: string;
            numero_protocolo: string | null;
        }>;
    };
}>();
</script>

<template>
    <div>
        <Heading title="Minhas Inscrições" :icon="ClipboardCheck" />
        <ul class="space-y-2">
            <li
                v-for="app in applications.data"
                :key="app.id"
                class="rounded border p-3"
            >
                {{ app.numero_protocolo ?? 'Sem protocolo' }} - {{ app.status }}
            </li>
        </ul>
    </div>
</template>
