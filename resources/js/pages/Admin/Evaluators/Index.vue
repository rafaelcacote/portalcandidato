<script setup lang="ts">
import { Users } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';

defineProps<{ assignments: { data: Array<Record<string, unknown>> } }>();
</script>

<template>
    <div>
        <Heading
            title="Avaliadores por Processo"
            :description="`Total: ${assignments.data.length}`"
            :icon="Users"
        />
    </div>
</template>
