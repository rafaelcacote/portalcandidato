<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, ClipboardList, FileWarning } from 'lucide-vue-next';
import { show as applicationShow } from '@/routes/candidate/applications';
import { index as processesIndex } from '@/routes/candidate/processes';

defineProps<{
    displayName: string;
    inscricoesEmAndamento: number;
    pendencias: number;
    highlightApplication: {
        id: number;
        process_title: string;
        status: string;
        numero_protocolo: string | null;
        kind: 'pendencia' | 'rascunho' | 'documento_recusado';
        detail?: string | null;
    } | null;
}>();

function statusLabel(status: string): string {
    const labels: Record<string, string> = {
        rascunho: 'Rascunho',
        em_analise: 'Em análise',
        pendencia: 'Pendência',
    };

    return labels[status] ?? status;
}

function highlightBadge(kind: string): { label: string; class: string } {
    if (kind === 'pendencia') {
        return {
            label: 'Ação necessária',
            class: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        };
    }

    if (kind === 'documento_recusado') {
        return {
            label: 'Documento recusado',
            class: 'bg-red-50 text-red-700 ring-1 ring-red-200',
        };
    }

    return {
        label: 'Continuar inscrição',
        class: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
    };
}
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl ring-1 ring-slate-200/70">
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 z-0 bg-cover bg-left bg-no-repeat"
            style="background-image: url('/img/admin-dashboard-hero-bg.png')"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-r from-transparent via-white/25 to-white/88 md:from-white/10 md:via-white/35 md:to-white/90"
        />

        <div
            class="relative z-10 grid gap-5 px-5 py-6 md:grid-cols-12 md:gap-6 md:px-8 md:py-8"
        >
            <div class="md:col-span-7 lg:col-span-7">
                <div
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/70 bg-emerald-50/90 px-3 py-1 text-[11px] font-bold tracking-widest text-emerald-700 uppercase shadow-sm"
                >
                    <span
                        class="size-1.5 animate-pulse rounded-full bg-emerald-500"
                    />
                    Área do candidato
                </div>

                <h1
                    class="mt-4 text-2xl font-bold tracking-tight text-slate-900 [text-shadow:0_0_24px_rgba(255,255,255,0.75)] sm:text-3xl md:text-[2rem] md:leading-tight"
                >
                    Bem-vindo de volta,<br />
                    <span
                        class="bg-gradient-to-r from-teal-600 via-emerald-500 to-teal-500 bg-clip-text text-transparent"
                    >
                        {{ displayName }}!
                    </span>
                </h1>

                <p
                    class="mt-3 max-w-md text-sm leading-relaxed text-slate-600 [text-shadow:0_0_18px_rgba(255,255,255,0.8)]"
                >
                    Acompanhe suas inscrições, resolva pendências e envie
                    documentos nos processos seletivos em que você participa.
                </p>

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200/80 bg-white/80 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm backdrop-blur-sm"
                    >
                        <span class="size-2 rounded-full bg-emerald-500" />
                        {{
                            inscricoesEmAndamento === 1
                                ? '1 inscrição em andamento'
                                : `${inscricoesEmAndamento} inscrições em andamento`
                        }}
                    </span>
                    <span
                        v-if="pendencias > 0"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200/80 bg-white/80 px-3 py-1.5 text-xs font-medium text-amber-700 shadow-sm backdrop-blur-sm"
                    >
                        <FileWarning class="size-3" />
                        {{
                            pendencias === 1
                                ? '1 pendência'
                                : `${pendencias} pendências`
                        }}
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200/80 bg-white/80 px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm backdrop-blur-sm"
                    >
                        <span class="size-2 rounded-full bg-emerald-500" />
                        Sem pendências
                    </span>
                </div>
            </div>

            <div class="flex md:col-span-5 md:justify-end lg:col-span-5">
                <div
                    v-if="highlightApplication"
                    class="flex w-full max-w-md flex-col gap-3 rounded-xl bg-white p-4 shadow-md ring-1 shadow-slate-200/70 ring-slate-200/80"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-500"
                        >
                            <span
                                class="size-1.5 animate-pulse rounded-full bg-amber-400"
                            />
                            Destaque
                        </span>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                            :class="
                                highlightBadge(highlightApplication.kind).class
                            "
                        >
                            {{
                                highlightBadge(highlightApplication.kind).label
                            }}
                        </span>
                    </div>

                    <h3
                        class="text-base leading-snug font-bold text-slate-900 sm:text-[1.05rem]"
                    >
                        {{ highlightApplication.process_title }}
                    </h3>

                    <div class="space-y-2.5">
                        <div class="flex items-start gap-2.5">
                            <div
                                class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
                            >
                                <ClipboardList class="size-3.5" />
                            </div>
                            <div>
                                <p
                                    class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase"
                                >
                                    Situação
                                </p>
                                <p
                                    class="mt-0.5 text-xs font-medium text-slate-700"
                                >
                                    {{
                                        statusLabel(highlightApplication.status)
                                    }}
                                    <span
                                        v-if="
                                            highlightApplication.numero_protocolo
                                        "
                                        class="text-slate-400"
                                    >
                                        ·
                                        {{
                                            highlightApplication.numero_protocolo
                                        }}
                                    </span>
                                </p>
                                <p
                                    v-if="highlightApplication.detail"
                                    class="mt-1 line-clamp-2 text-xs text-red-600"
                                >
                                    {{ highlightApplication.detail }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <Link
                        :href="
                            applicationShow.url({
                                application: highlightApplication.id,
                            })
                        "
                        class="mt-0.5"
                    >
                        <button
                            type="button"
                            class="group inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-teal-600/25 transition-all duration-200 hover:from-teal-500 hover:to-emerald-500 hover:shadow-lg hover:shadow-teal-600/30 focus-visible:ring-2 focus-visible:ring-teal-400/60 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            Ver inscrição
                            <ArrowRight
                                class="size-4 transition-transform duration-200 group-hover:translate-x-0.5"
                            />
                        </button>
                    </Link>
                </div>

                <div
                    v-else
                    class="flex min-h-[10rem] w-full max-w-md flex-col items-center justify-center rounded-xl border border-dashed border-slate-300/60 bg-white/50 p-5 text-center backdrop-blur-sm md:ml-auto"
                >
                    <p class="text-sm font-semibold text-slate-600">
                        Tudo em dia
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Nenhuma pendência ou inscrição aguardando sua ação.
                    </p>
                    <Link :href="processesIndex().url" class="mt-4">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-md"
                        >
                            Ver processos abertos
                            <ArrowRight class="size-3.5" />
                        </button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
