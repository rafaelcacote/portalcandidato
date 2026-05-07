<script setup lang="ts">
import { Users } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';

defineProps<{
    processes: Array<Record<string, unknown>>;
    evaluators: Array<Record<string, unknown>>;
}>();
</script>

<template>
    <div>
        <Heading
            title="Vincular Avaliador"
            description="Selecione processo e avaliador para criar a atribuição."
            :icon="Users"
        />
    </div>
</template>
