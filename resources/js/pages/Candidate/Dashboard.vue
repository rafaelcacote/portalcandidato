<script setup lang="ts">
import { LayoutGrid } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
</script>

<template>
    <div>
        <Heading
            title="Painel do Candidato"
            description="Acompanhe inscrições, pendências e resultados."
            :icon="LayoutGrid"
        />
    </div>
</template>
