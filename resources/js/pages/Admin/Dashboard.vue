<script setup lang="ts">
import { LayoutGrid } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
</script>

<template>
    <div>
        <Heading
            title="Painel Administrativo"
            description="Gerencie processos, avaliadores e relatórios."
            :icon="LayoutGrid"
        />
    </div>
</template>
