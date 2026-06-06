<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import {
    index as applicationsIndex,
    show as applicationShow,
} from '@/routes/candidate/applications';

defineProps<{
    pendenciasInscricao: Array<{
        id: number;
        process_title: string;
        numero_protocolo: string | null;
    }>;
    documentosRecusados: Array<{
        id: number;
        application_id: number;
        nome_arquivo: string;
        tipo_documento: string;
        process_title: string;
        motivo_recusa: string | null;
    }>;
}>();
</script>

<template>
    <div
        class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60"
    >
        <div
            class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex size-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600"
                >
                    <AlertTriangle class="size-3.5" />
                </div>
                <h2 class="text-sm font-semibold text-slate-900">Pendências</h2>
            </div>
            <Link
                :href="applicationsIndex.url()"
                class="text-xs font-semibold text-teal-600 transition-colors hover:text-teal-700"
            >
                Minhas inscrições
            </Link>
        </div>

        <div
            v-if="pendenciasInscricao.length || documentosRecusados.length"
            class="divide-y divide-slate-100"
        >
            <div v-if="pendenciasInscricao.length" class="px-5 py-4">
                <p
                    class="mb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                >
                    Inscrições
                </p>
                <ul class="space-y-2">
                    <li
                        v-for="row in pendenciasInscricao"
                        :key="'p-' + row.id"
                        class="rounded-lg border border-amber-100 bg-amber-50/40 px-3 py-2.5"
                    >
                        <Link
                            :href="applicationShow.url({ application: row.id })"
                            class="text-sm font-semibold text-amber-900 hover:text-amber-700"
                        >
                            {{ row.process_title }}
                        </Link>
                        <p
                            v-if="row.numero_protocolo"
                            class="text-xs text-amber-700/80"
                        >
                            {{ row.numero_protocolo }}
                        </p>
                    </li>
                </ul>
            </div>

            <div v-if="documentosRecusados.length" class="px-5 py-4">
                <p
                    class="mb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                >
                    Documentos recusados
                </p>
                <ul class="space-y-2">
                    <li
                        v-for="doc in documentosRecusados"
                        :key="'d-' + doc.id"
                        class="rounded-lg border border-red-100 bg-red-50/40 px-3 py-2.5"
                    >
                        <Link
                            :href="
                                applicationShow.url({
                                    application: doc.application_id,
                                })
                            "
                            class="text-sm font-semibold text-red-900 hover:text-red-700"
                        >
                            {{ doc.tipo_documento }}
                        </Link>
                        <p class="text-xs text-red-800/70">
                            {{ doc.process_title }} · {{ doc.nome_arquivo }}
                        </p>
                        <p
                            v-if="doc.motivo_recusa"
                            class="mt-1 line-clamp-2 text-xs text-red-600"
                        >
                            {{ doc.motivo_recusa }}
                        </p>
                    </li>
                </ul>
            </div>
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center px-6 py-14 text-center"
        >
            <div
                class="flex size-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500"
            >
                <AlertTriangle class="size-6" />
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-700">
                Nenhuma pendência
            </p>
            <p class="mt-1 max-w-xs text-xs text-slate-400">
                Você não possui inscrições em pendência nem documentos recusados
                no momento.
            </p>
        </div>
    </div>
</template>
