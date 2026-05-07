<script setup lang="ts">
import { FileText } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';

defineProps<{
    processes: { data: Array<{ id: number; titulo: string; status: string }> };
}>();
</script>

<template>
    <div>
        <Heading title="Processos Abertos" :icon="FileText" />
        <ul class="space-y-2">
            <li
                v-for="process in processes.data"
                :key="process.id"
                class="rounded border p-3"
            >
                {{ process.titulo }}
            </li>
        </ul>
    </div>
</template>
