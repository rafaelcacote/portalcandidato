<script setup lang="ts">
import { ClipboardCheck } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';

defineProps<{
    processes: Array<{ id: number; titulo: string; status: string }>;
}>();
</script>

<template>
    <div>
        <Heading title="Relatórios Administrativos" :icon="ClipboardCheck" />
        <ul class="space-y-2">
            <li v-for="process in processes" :key="process.id">
                {{ process.titulo }} - {{ process.status }}
            </li>
        </ul>
    </div>
</template>
