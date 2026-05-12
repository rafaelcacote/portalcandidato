<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    Clock,
    FileText,
    Lock,
    LogIn,
    Mail,
    Shield,
} from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: '',
        description: '',
        contentMaxWidth: 'max-w-6xl',
        backdrop: 'login-fundo',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <div>
        <Head title="Entrar" />

        <div class="relative w-full min-w-0 pb-6 pt-4 max-lg:pb-10 sm:pt-6 lg:pb-8 lg:pt-14">
            <div
                class="relative grid min-w-0 w-full grid-cols-1 gap-6 sm:gap-8 lg:grid-cols-2 lg:gap-12 xl:gap-16"
            >
                <!-- Coluna institucional: apenas desktop / tablet largo -->
                <div
                    class="mx-auto hidden min-w-0 w-full max-w-xl text-center lg:block lg:mx-0 lg:max-w-none lg:pt-2 lg:text-left"
                >
                    <p
                        class="mb-3 text-[10px] font-bold uppercase tracking-[0.28em] text-emerald-700 sm:mb-4 sm:text-[11px]"
                    >
                        Realização
                    </p>

                    <div
                        class="flex flex-col items-center justify-center gap-4 sm:flex-row sm:flex-nowrap sm:gap-6 lg:justify-start"
                    >
                        <img
                            src="/img/logo_proensp.svg"
                            alt="ProEnSP – Programa de Pós-Graduação em Enfermagem em Saúde Pública"
                            class="h-12 w-auto max-w-[min(100%,220px)] object-contain drop-shadow-sm sm:h-16 sm:max-w-[min(100%,260px)]"
                            loading="eager"
                        />

                        <div class="h-px w-20 shrink-0 rounded-full bg-emerald-600/35 sm:hidden" />

                        <div
                            class="hidden shrink-0 rounded-full bg-emerald-600/35 sm:block sm:h-16 sm:w-px"
                        />

                        <img
                            src="/img/uea_00.svg"
                            alt="Universidade do Estado do Amazonas"
                            class="h-12 w-auto max-w-[min(100%,240px)] object-contain drop-shadow-sm sm:h-16 sm:max-w-[min(100%,280px)]"
                            loading="eager"
                        />
                    </div>

                    <h1
                        class="mt-5 text-2xl font-extrabold tracking-tight text-slate-900 sm:mt-7 sm:text-3xl lg:mt-8 lg:text-4xl"
                    >
                        Entrar
                    </h1>

                    <p
                        class="mx-auto mt-3 max-w-xl text-sm leading-snug text-slate-600 sm:text-base sm:leading-relaxed lg:mx-0"
                    >
                        Informe seu e-mail e senha para acessar o portal do candidato.
                    </p>

                    <div
                        class="mt-3 inline-flex max-w-full items-center gap-2 rounded-full border border-emerald-200/90 bg-white/90 px-3 py-1.5 text-[11px] font-medium leading-snug text-emerald-800 shadow-sm backdrop-blur-sm sm:mt-5 sm:gap-2.5 sm:px-5 sm:text-sm lg:mt-6"
                    >
                        <Shield :size="15" class="shrink-0 text-emerald-700" />
                        <span class="text-left sm:text-center lg:text-left">
                            Conexão segura. Seus dados são protegidos.
                        </span>
                    </div>

                    <ul class="mt-6 space-y-3.5 text-left sm:mt-9 sm:space-y-5 lg:mt-10">
                        <li class="flex gap-3 sm:gap-4">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#39b4b9]/15 text-[#0a7f84] dark:text-[#7ad6d9] sm:h-10 sm:w-10"
                            >
                                <Lock :size="18" stroke-width="2.25" />
                            </div>
                            <p class="text-[13px] leading-relaxed text-slate-600 sm:text-[15px]">
                                <span class="font-semibold text-slate-900">Acesso seguro</span>
                                — Seus dados pessoais protegidos com criptografia de ponta.
                            </p>
                        </li>
                        <li class="flex gap-3 sm:gap-4">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#39b4b9]/15 text-[#0a7f84] dark:text-[#7ad6d9] sm:h-10 sm:w-10"
                            >
                                <FileText :size="18" stroke-width="2.25" />
                            </div>
                            <p class="text-[13px] leading-relaxed text-slate-600 sm:text-[15px]">
                                <span class="font-semibold text-slate-900">Processo simplificado</span>
                                — Acompanhe todas as etapas do processo seletivo em um só lugar.
                            </p>
                        </li>
                        <li class="flex gap-3 sm:gap-4">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#39b4b9]/15 text-[#0a7f84] dark:text-[#7ad6d9] sm:h-10 sm:w-10"
                            >
                                <Clock :size="18" stroke-width="2.25" />
                            </div>
                            <p class="text-[13px] leading-relaxed text-slate-600 sm:text-[15px]">
                                <span class="font-semibold text-slate-900">Salvamento automático</span>
                                — Seus dados são salvos automaticamente durante o preenchimento.
                            </p>
                        </li>
                    </ul>
                </div>

                <!-- Formulário: no mobile só logos + card -->
                <div
                    class="mx-auto min-w-0 w-full max-w-md lg:mx-0 lg:max-w-none lg:justify-self-end xl:max-w-[440px]"
                >
                    <div
                        class="mb-6 flex flex-row flex-nowrap items-center justify-center gap-2.5 sm:mb-8 sm:gap-6 lg:hidden"
                    >
                        <img
                            src="/img/logo_proensp.svg"
                            alt="ProEnSP – Programa de Pós-Graduação em Enfermagem em Saúde Pública"
                            class="h-11 w-auto max-w-[min(52%,11rem)] shrink object-contain drop-shadow-sm sm:h-14 sm:max-w-[min(100%,240px)]"
                            loading="eager"
                        />
                        <div
                            class="h-11 w-px shrink-0 rounded-full bg-emerald-600/35 sm:h-14"
                        />
                        <img
                            src="/img/uea_00.svg"
                            alt="Universidade do Estado do Amazonas"
                            class="h-11 w-auto max-w-[min(52%,11rem)] shrink object-contain drop-shadow-sm sm:h-14 sm:max-w-[min(100%,260px)]"
                            loading="eager"
                        />
                    </div>
                    <div
                        v-if="status"
                        class="mb-4 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-center text-sm font-medium text-emerald-900 shadow-sm dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-100"
                    >
                        {{ status }}
                    </div>

                    <Form
                        :action="store.url()"
                        method="post"
                        :reset-on-success="['password']"
                        v-slot="{ errors, processing }"
                        class="flex flex-col gap-5 sm:gap-6"
                    >
                        <Card
                            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xl shadow-slate-900/[0.06] dark:border-slate-800 dark:bg-slate-950"
                        >
                            <CardContent class="space-y-5 p-5 sm:space-y-6 sm:p-7 md:p-8">
                                <div class="flex items-start gap-3 sm:gap-4">
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#39b4b9]/12 text-[#0a7f84] dark:text-[#7ad6d9]"
                                    >
                                        <Mail :size="22" stroke-width="2" />
                                    </div>
                                    <div class="min-w-0 pt-0.5 text-left">
                                        <h2 class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100">
                                            Acesso ao portal do candidato
                                        </h2>
                                        <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                                            Use as credenciais do seu cadastro de candidato.
                                        </p>
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="email" class="text-slate-900 dark:text-slate-100">E-mail</Label>
                                    <div class="relative">
                                        <Mail
                                            class="pointer-events-none absolute left-3 top-1/2 size-[18px] -translate-y-1/2 text-slate-400"
                                            aria-hidden="true"
                                        />
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            required
                                            autofocus
                                            :tabindex="1"
                                            autocomplete="email"
                                            placeholder="nome@exemplo.com"
                                            class="h-12 border-slate-200 bg-white pl-10 text-base shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:h-11 md:text-sm"
                                        />
                                    </div>
                                    <InputError :message="errors.email" />
                                </div>

                                <div class="grid gap-2">
                                    <div
                                        class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-2"
                                    >
                                        <Label
                                            for="password"
                                            class="text-slate-900 dark:text-slate-100"
                                        >
                                            Senha
                                        </Label>
                                        <TextLink
                                            v-if="canResetPassword"
                                            :href="request()"
                                            class="self-start text-xs font-medium text-[#0a7f84] underline decoration-[#0a7f84]/25 underline-offset-2 hover:text-[#087078] hover:decoration-[#0a7f84]/50 sm:self-auto sm:text-sm dark:text-[#5ec9ce] dark:hover:text-[#7ad6d9]"
                                            :tabindex="5"
                                        >
                                            Esqueceu a senha?
                                        </TextLink>
                                    </div>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        required
                                        :tabindex="2"
                                        autocomplete="current-password"
                                        placeholder="Sua senha"
                                        class="h-12 border-slate-200 bg-white text-base shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:h-11 md:text-sm"
                                    />
                                    <InputError :message="errors.password" />
                                </div>

                                <Button
                                    type="submit"
                                    class="h-12 w-full rounded-xl border border-[#39b4b9]/25 bg-[#39b4b9] text-base font-semibold text-white shadow-md transition-transform hover:bg-[#2ea0a6] active:scale-[0.99] focus-visible:border-[#39b4b9]/40 focus-visible:ring-[#39b4b9]/35 sm:h-11 dark:border-[#39b4b9]/40 dark:bg-[#39b4b9] dark:hover:bg-[#4dc8cd]"
                                    :tabindex="3"
                                    :disabled="processing"
                                    data-test="login-button"
                                >
                                    <Spinner v-if="processing" />
                                    <template v-else>
                                        <LogIn class="mr-2 size-[18px] shrink-0 opacity-95" aria-hidden="true" />
                                        Entrar
                                    </template>
                                </Button>
                            </CardContent>
                        </Card>

                        <p
                            v-if="canRegister"
                            class="pb-[max(0.75rem,env(safe-area-inset-bottom,0px))] text-center text-sm text-slate-600 dark:text-slate-400"
                        >
                            Não tem uma conta?
                            <TextLink
                                :href="register()"
                                class="font-semibold text-[#0a7f84] underline decoration-[#0a7f84]/30 underline-offset-4 hover:text-[#087078] dark:text-[#5ec9ce] dark:hover:text-[#7ad6d9]"
                                :tabindex="6"
                            >
                                Cadastre-se
                            </TextLink>
                        </p>
                    </Form>
                </div>
            </div>
        </div>
    </div>
</template>
