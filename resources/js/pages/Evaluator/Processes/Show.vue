<script setup lang="ts">
import { ClipboardCheck } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';

defineProps<{
    selectionProcess: {
        titulo: string;
        applications: Array<Record<string, unknown>>;
    };
}>();
</script>

<template>
    <div>
        <Heading
            :title="selectionProcess.titulo"
            :description="`Candidatos: ${selectionProcess.applications.length}`"
            :icon="ClipboardCheck"
        />
    </div>
</template>
