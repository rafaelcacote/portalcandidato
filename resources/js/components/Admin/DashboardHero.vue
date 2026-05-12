<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Calendar } from 'lucide-vue-next';
import { show as processShow } from '@/routes/admin/processes';

defineProps<{
    displayName: string;
    processesAtivo: number;
    applicationsTotal: number;
    highlightProcess: {
        id: number;
        titulo: string;
        inscricao_inicio_em: string | null;
        inscricao_fim_em: string | null;
    } | null;
}>();

function formatDate(dateStr: string | null): string {
    if (!dateStr) {
        return '—';
    }

    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <!-- Hero: imagem de fundo + leve véu para legibilidade do texto -->
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

        <div class="relative z-10 grid gap-5 px-5 py-6 md:grid-cols-12 md:gap-6 md:px-8 md:py-8">
            <!-- Left: greeting -->
            <div class="md:col-span-7 lg:col-span-7">
                <!-- Live badge -->
                <div
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/70 bg-emerald-50/90 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-emerald-700 shadow-sm"
                >
                    <span class="size-1.5 animate-pulse rounded-full bg-emerald-500" />
                    Sistema ativo
                </div>

                <h1
                    class="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl md:text-[2rem] md:leading-tight [text-shadow:0_0_24px_rgba(255,255,255,0.75)]"
                >
                    Bem-vindo de volta,<br />
                    <span
                        class="bg-gradient-to-r from-teal-600 via-emerald-500 to-teal-500 bg-clip-text text-transparent"
                    >
                        {{ displayName }}!
                    </span>
                </h1>

                <p class="mt-3 max-w-md text-sm leading-relaxed text-slate-600 [text-shadow:0_0_18px_rgba(255,255,255,0.8)]">
                    Acompanhe o desempenho dos processos seletivos e gerencie todas as atividades da
                    plataforma.
                </p>

                <!-- Status pills -->
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200/80 bg-white/80 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm backdrop-blur-sm"
                    >
                        <span class="size-2 rounded-full bg-emerald-500" />
                        {{
                            processesAtivo === 1
                                ? '1 processo ativo'
                                : `${processesAtivo} processos ativos`
                        }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200/80 bg-white/80 px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm backdrop-blur-sm"
                    >
                        {{ applicationsTotal }} inscrições
                    </span>
                </div>
            </div>

            <!-- Right: process highlight card (max width so it does not feel stretched) -->
            <div class="flex md:col-span-5 lg:col-span-5 md:justify-end">
                <!-- Active process card -->
                <div
                    v-if="highlightProcess"
                    class="flex w-full max-w-md flex-col gap-3 rounded-xl bg-white p-4 shadow-md shadow-slate-200/70 ring-1 ring-slate-200/80"
                >
                    <!-- Top row: label + badge -->
                    <div class="flex items-start justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-500">
                            <span class="size-1.5 animate-pulse rounded-full bg-emerald-500" />
                            Processo seletivo ativo
                        </span>
                        <span
                            class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200"
                        >
                            Inscrições abertas
                        </span>
                    </div>

                    <!-- Title -->
                    <h3 class="text-base font-bold leading-snug text-slate-900 sm:text-[1.05rem]">
                        {{ highlightProcess.titulo }}
                    </h3>

                    <!-- Info rows -->
                    <div class="space-y-2.5">
                        <div class="flex items-start gap-2.5">
                            <div class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <Calendar class="size-3.5" />
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                    Período de inscrições
                                </p>
                                <p class="mt-0.5 text-xs font-medium text-slate-700">
                                    {{ formatDate(highlightProcess.inscricao_inicio_em) }}
                                    <span class="mx-1 text-slate-400">a</span>
                                    {{ formatDate(highlightProcess.inscricao_fim_em) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- CTA button -->
                    <Link
                        :href="processShow({ selectionProcess: highlightProcess.id }).url"
                        class="mt-0.5"
                    >
                        <button
                            type="button"
                            class="group inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-teal-600/25 transition-all duration-200 hover:from-teal-500 hover:to-emerald-500 hover:shadow-lg hover:shadow-teal-600/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-400/60 focus-visible:ring-offset-2"
                        >
                            Acessar processo
                            <ArrowRight
                                class="size-4 transition-transform duration-200 group-hover:translate-x-0.5"
                            />
                        </button>
                    </Link>
                </div>

                <!-- Empty state -->
                <div
                    v-else
                    class="flex w-full max-w-md min-h-[10rem] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300/60 bg-white/50 p-5 text-center backdrop-blur-sm md:ml-auto"
                >
                    <p class="text-sm font-semibold text-slate-600">Nenhum processo ativo</p>
                    <p class="mt-1 text-xs text-slate-400">
                        Publique um processo para exibir o destaque aqui.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
