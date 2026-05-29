<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Shield } from 'lucide-vue-next';
import LgpdPrivacyPolicyContent from '@/components/Lgpd/LgpdPrivacyPolicyContent.vue';
import { login } from '@/routes';

defineProps<{
    dataController: string;
    contactEmail: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <Head title="Política de Privacidade" />

        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
            <Link
                :href="login()"
                class="mb-8 inline-flex items-center gap-2 text-sm font-medium text-emerald-800 hover:text-emerald-900 dark:text-emerald-400"
            >
                <ArrowLeft :size="16" aria-hidden="true" />
                Voltar ao portal
            </Link>

            <div class="mb-8 flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
                >
                    <Shield :size="24" aria-hidden="true" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Política de Privacidade e Proteção de Dados
                    </h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        Em conformidade com a Lei nº 13.709/2018 (LGPD)
                    </p>
                </div>
            </div>

            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800 dark:bg-slate-900"
            >
                <LgpdPrivacyPolicyContent
                    :data-controller="dataController"
                    :contact-email="contactEmail"
                />
            </div>
        </div>
    </div>
</template>
