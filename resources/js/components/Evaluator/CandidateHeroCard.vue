<script setup lang="ts">
import { CalendarDays, ChevronRight, Hash, Layers } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CandidateStatusBadge from '@/components/Evaluator/CandidateStatusBadge.vue';

const props = defineProps<{
    application: {
        id: number;
        status: string;
        numero_protocolo: string | null;
        created_at: string;
        user: {
            name: string;
            email: string;
            cpf?: string | null;
            foto_url?: string | null;
        };
        selectionProcess?: { titulo: string } | null;
    };
}>();

const photoErrored = ref(false);

watch(
    () => props.application.user.foto_url,
    () => {
        photoErrored.value = false;
    },
);

const showPhoto = computed(
    () => Boolean(props.application.user.foto_url) && !photoErrored.value,
);

function getInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
}

const formattedDate = computed(() => {
    if (!props.application.created_at) {
        return '—';
    }
    return new Date(props.application.created_at).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80 shadow-sm">
        <!-- Decorative background — right side only, subtle -->
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute right-0 top-0 h-full w-1/2 bg-gradient-to-l from-teal-50/50 via-emerald-50/20 to-transparent" />
            <div class="absolute -right-16 top-0 h-full w-48 translate-x-0 bg-[url('/img/admin-dashboard-hero-bg.png')] bg-cover bg-right bg-no-repeat opacity-10" />
        </div>

        <div class="relative flex items-center gap-5 px-5 py-5 sm:gap-6 sm:px-6">
            <!-- Avatar -->
            <div class="relative shrink-0">
                <div
                    class="size-14 overflow-hidden rounded-xl ring-2 ring-white ring-offset-1 ring-offset-white shadow-md sm:size-16"
                >
                    <img
                        v-if="showPhoto"
                        :src="application.user.foto_url!"
                        :alt="`Foto de ${application.user.name}`"
                        class="size-full object-cover"
                        @error="photoErrored = true"
                    />
                    <div
                        v-else
                        class="flex size-full items-center justify-center bg-gradient-to-br from-teal-500 to-emerald-600 text-lg font-bold text-white"
                    >
                        {{ getInitials(application.user.name) }}
                    </div>
                </div>
                <!-- Online dot -->
                <span class="absolute -bottom-0.5 -right-0.5 block size-3 rounded-full bg-emerald-500 ring-2 ring-white" />
            </div>

            <!-- Info -->
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-bold tracking-tight text-slate-900 sm:text-lg">
                            {{ application.user.name }}
                        </h2>
                        <p class="truncate text-sm text-slate-500">{{ application.user.email }}</p>
                    </div>
                    <CandidateStatusBadge :status="application.status" size="md" />
                </div>

                <!-- Meta chips -->
                <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-slate-600">
                    <span class="flex items-center gap-1.5">
                        <Hash class="size-3.5 text-slate-400" />
                        <span class="font-medium">Inscrição</span>
                        <span class="font-semibold text-slate-800">#{{ application.numero_protocolo ?? application.id }}</span>
                    </span>

                    <span class="flex items-center gap-1.5">
                        <CalendarDays class="size-3.5 text-slate-400" />
                        <span class="font-medium">Data da inscrição</span>
                        <span class="font-semibold text-slate-800">{{ formattedDate }}</span>
                    </span>

                    <span v-if="application.selectionProcess" class="flex items-center gap-1.5">
                        <Layers class="size-3.5 text-teal-500" />
                        <span class="font-medium">Processo seletivo</span>
                        <span class="line-clamp-1 max-w-xs font-semibold text-slate-800">
                            {{ application.selectionProcess.titulo }}
                        </span>
                    </span>
                </div>
            </div>

            <ChevronRight class="hidden size-5 shrink-0 text-slate-300 lg:block" />
        </div>
    </div>
</template>
