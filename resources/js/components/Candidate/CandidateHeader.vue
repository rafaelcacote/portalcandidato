<script setup lang="ts">
import { Shield } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        notice?: string;
        /** Menos altura visual — adequado para login e telas curtas. */
        compact?: boolean;
        /** Exibe a faixa com ícone de escudo (ex.: mensagem de segurança). */
        showNotice?: boolean;
    }>(),
    {
        title: 'Cadastro de Candidato',
        subtitle:
            'Preencha suas informações com atenção para participar do\nprocesso seletivo.',
        notice: 'O sistema salva automaticamente seu progresso.',
        compact: false,
        showNotice: true,
    },
);
</script>

<template>
    <section
        class="relative overflow-hidden rounded-[2rem] border border-slate-200/70 bg-[#eef8f9] shadow-lg sm:rounded-[2.5rem]"
    >
        <!-- Fundo: arte institucional (PNG em public/img) -->
        <div
            class="absolute inset-0 bg-cover bg-top bg-no-repeat sm:bg-center"
            style="
                background-image: url('/img/82fe113f-2a84-41e2-92a3-c5661ddf2f52.png');
            "
            role="presentation"
        />

        <!-- Vinheta suave no centro (evita “faixa” branca forte no meio da arte) -->
        <div
            class="pointer-events-none absolute inset-0"
            style="
                background: radial-gradient(
                    ellipse 80% 70% at 50% 52%,
                    rgb(255 255 255 / 0.78) 0%,
                    rgb(255 255 255 / 0.22) 52%,
                    transparent 100%
                );
            "
        />

        <div
            :class="
                compact
                    ? 'relative z-10 flex flex-col items-center justify-center px-6 pt-7 pb-7 text-center sm:px-10 sm:pt-8 sm:pb-8'
                    : 'relative z-10 flex flex-col items-center justify-center px-6 pt-8 pb-8 text-center sm:px-12 sm:pt-9 sm:pb-9'
            "
        >
            <p
                :class="
                    compact
                        ? 'mb-3 text-[10px] font-bold tracking-[0.28em] text-emerald-700 uppercase sm:mb-4 sm:text-[11px]'
                        : 'mb-4 text-[10px] font-bold tracking-[0.28em] text-emerald-700 uppercase sm:mb-5 sm:text-[11px]'
                "
            >
                Realização
            </p>

            <div
                :class="
                    compact
                        ? 'flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-6'
                        : 'flex flex-col items-center justify-center gap-4 sm:flex-row sm:gap-8'
                "
            >
                <img
                    src="/img/logo_proensp.svg"
                    alt="ProEnSP – Programa de Pós-Graduação em Enfermagem em Saúde Pública"
                    :class="
                        compact
                            ? 'h-12 w-auto max-w-[min(100%,200px)] object-contain drop-shadow-sm sm:h-[3.75rem]'
                            : 'h-14 w-auto max-w-[min(100%,220px)] object-contain drop-shadow-sm sm:h-[4.5rem]'
                    "
                    loading="eager"
                />

                <div
                    class="h-px w-20 shrink-0 rounded-full bg-emerald-600/35 sm:hidden"
                />

                <div
                    class="hidden shrink-0 rounded-full bg-emerald-600/35 sm:block"
                    :class="compact ? 'h-12 w-px sm:h-14' : 'h-14 w-px sm:h-16'"
                />

                <img
                    src="/img/uea_00.svg"
                    alt="Universidade do Estado do Amazonas"
                    :class="
                        compact
                            ? 'h-12 w-auto max-w-[min(100%,220px)] object-contain drop-shadow-sm sm:h-[3.75rem]'
                            : 'h-14 w-auto max-w-[min(100%,240px)] object-contain drop-shadow-sm sm:h-[4.5rem]'
                    "
                    loading="eager"
                />
            </div>

            <h1
                :class="
                    compact
                        ? 'mt-6 max-w-3xl text-2xl font-extrabold tracking-tight text-slate-900 sm:mt-7 sm:text-3xl'
                        : 'mt-8 max-w-3xl text-2xl font-extrabold tracking-tight text-slate-900 sm:mt-10 sm:text-4xl'
                "
            >
                {{ title }}
            </h1>

            <p
                class="mx-auto mt-3 max-w-xl text-sm leading-relaxed whitespace-pre-line text-slate-600 sm:text-base"
            >
                {{ subtitle }}
            </p>

            <div
                v-if="showNotice"
                :class="
                    compact
                        ? 'mt-4 inline-flex max-w-full items-center gap-2 rounded-full border border-emerald-200/90 bg-white/85 px-4 py-1.5 text-xs font-medium text-emerald-800 shadow-sm backdrop-blur-sm sm:mt-5 sm:gap-2.5 sm:px-5 sm:text-sm'
                        : 'mt-5 inline-flex max-w-full items-center gap-2 rounded-full border border-emerald-200/90 bg-white/85 px-4 py-2 text-xs font-medium text-emerald-800 shadow-sm backdrop-blur-sm sm:mt-6 sm:gap-2.5 sm:px-5 sm:text-sm'
                "
            >
                <Shield :size="15" class="shrink-0 text-emerald-700" />
                <span class="text-left sm:text-center">{{ notice }}</span>
            </div>
        </div>
    </section>
</template>
