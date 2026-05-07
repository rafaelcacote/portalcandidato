<script setup lang="ts">
import { LayoutGrid } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
</script>

<template>
    <div>
        <Heading
            title="Painel do Avaliador"
            description="Fila de análise e processos atribuídos."
            :icon="LayoutGrid"
        />
    </div>
</template>
