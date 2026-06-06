<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ClipboardList,
    FolderOpen,
    Search,
    UserCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { index as applicationsIndex } from '@/routes/candidate/applications';
import { index as documentsIndex } from '@/routes/candidate/documents';
import { index as processesIndex } from '@/routes/candidate/processes';
import { edit as profileEdit } from '@/routes/profile';

const actions = computed(() => [
    {
        title: 'Processos abertos',
        description:
            'Consulte editais com inscrições abertas e inicie uma nova inscrição.',
        href: processesIndex().url,
        icon: Search,
        bgClass: 'bg-emerald-50 group-hover:bg-emerald-100/60',
        iconColor: 'text-emerald-700',
        arrowColor: 'text-emerald-500',
        borderColor: 'border-emerald-100',
        accentBar: 'bg-emerald-500',
    },
    {
        title: 'Meus documentos',
        description:
            'Envie, substitua ou acompanhe documentos das suas inscrições.',
        href: documentsIndex.url(),
        icon: FolderOpen,
        bgClass: 'bg-sky-50 group-hover:bg-sky-100/60',
        iconColor: 'text-sky-700',
        arrowColor: 'text-sky-500',
        borderColor: 'border-sky-100',
        accentBar: 'bg-sky-500',
    },
    {
        title: 'Minhas inscrições',
        description:
            'Veja o status de todas as inscrições e continue rascunhos.',
        href: applicationsIndex.url(),
        icon: ClipboardList,
        bgClass: 'bg-violet-50 group-hover:bg-violet-100/60',
        iconColor: 'text-violet-700',
        arrowColor: 'text-violet-500',
        borderColor: 'border-violet-100',
        accentBar: 'bg-violet-500',
    },
    {
        title: 'Meu perfil',
        description:
            'Visualize e edite seus dados pessoais, documento e endereço.',
        href: profileEdit().url,
        icon: UserCircle,
        bgClass: 'bg-amber-50 group-hover:bg-amber-100/60',
        iconColor: 'text-amber-800',
        arrowColor: 'text-amber-600',
        borderColor: 'border-amber-100',
        accentBar: 'bg-amber-500',
    },
]);
</script>

<template>
    <div>
        <h2 class="mb-4 text-sm font-semibold tracking-tight text-slate-800">
            Ações rápidas
        </h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="action in actions"
                :key="action.title"
                :href="action.href"
                class="group relative flex items-start gap-3.5 overflow-hidden rounded-xl border bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                :class="action.borderColor"
            >
                <div
                    class="absolute top-0 bottom-0 left-0 w-0.5 rounded-full opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                    :class="action.accentBar"
                />

                <div
                    :class="[
                        'flex size-10 shrink-0 items-center justify-center rounded-xl transition-colors duration-200',
                        action.bgClass,
                        action.iconColor,
                    ]"
                >
                    <component :is="action.icon" class="size-5" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ action.title }}
                    </p>
                    <p
                        class="mt-0.5 text-[11px] leading-relaxed text-slate-400"
                    >
                        {{ action.description }}
                    </p>
                </div>

                <ArrowRight
                    :class="[
                        'mt-0.5 size-4 shrink-0 opacity-0 transition-all duration-200 group-hover:translate-x-0.5 group-hover:opacity-100',
                        action.arrowColor,
                    ]"
                />
            </Link>
        </div>
    </div>
</template>
