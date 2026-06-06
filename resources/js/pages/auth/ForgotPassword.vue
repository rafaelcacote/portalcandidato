<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, KeyRound, Mail } from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: '',
        description: '',
        contentMaxWidth: 'max-w-md',
        backdrop: 'login-fundo',
    },
});

const props = defineProps<{
    status?: string;
}>();

const linkSent = computed(() => Boolean(props.status));
</script>

<template>
    <div>
        <Head title="Esqueceu a senha?" />

        <Card
            class="overflow-hidden border-slate-200/90 bg-white shadow-xl shadow-slate-900/[0.06] dark:border-slate-800 dark:bg-slate-950"
        >
            <CardHeader
                class="space-y-4 border-b border-slate-200/80 bg-gradient-to-br from-[#39b4b9]/10 via-transparent to-emerald-500/5 pt-6 pb-6 dark:border-slate-800"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#39b4b9]/12 text-[#0a7f84] ring-1 ring-[#39b4b9]/25 dark:text-[#7ad6d9]"
                >
                    <KeyRound :size="32" stroke-width="1.75" />
                </div>

                <div class="space-y-2 text-center">
                    <CardTitle
                        class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl dark:text-slate-100"
                    >
                        Esqueceu a senha?
                    </CardTitle>
                    <CardDescription
                        class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-slate-400"
                    >
                        Informe o e-mail do seu cadastro. Enviaremos um link
                        para você criar uma nova senha.
                    </CardDescription>
                </div>
            </CardHeader>

            <CardContent class="space-y-6 pt-6">
                <div
                    v-if="linkSent"
                    class="flex items-start gap-3 rounded-xl border border-emerald-500/25 bg-emerald-500/8 px-4 py-3.5 text-sm text-emerald-800 dark:text-emerald-200"
                    role="status"
                >
                    <CheckCircle2
                        class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                    />
                    <p>{{ status }}</p>
                </div>

                <Form
                    v-bind="email.form()"
                    class="space-y-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label
                            for="email"
                            class="text-slate-900 dark:text-slate-100"
                            >E-mail</Label
                        >
                        <div class="relative">
                            <Mail
                                class="pointer-events-none absolute top-1/2 left-3 size-[18px] -translate-y-1/2 text-slate-400"
                                aria-hidden="true"
                            />
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="nome@exemplo.com"
                                class="h-12 border-slate-200 bg-white pl-10 text-base shadow-sm sm:h-11 md:text-sm dark:border-slate-700 dark:bg-slate-900"
                            />
                        </div>
                        <InputError :message="errors.email" />
                    </div>

                    <Button
                        type="submit"
                        class="h-12 w-full rounded-xl border border-[#39b4b9]/25 bg-[#39b4b9] text-base font-semibold text-white shadow-md transition-transform hover:bg-[#2ea0a6] focus-visible:border-[#39b4b9]/40 focus-visible:ring-[#39b4b9]/35 active:scale-[0.99] sm:h-11 dark:border-[#39b4b9]/40 dark:bg-[#39b4b9] dark:hover:bg-[#4dc8cd]"
                        :disabled="processing"
                        data-test="email-password-reset-link-button"
                    >
                        <Spinner v-if="processing" />
                        Enviar link de redefinição
                    </Button>
                </Form>

                <div
                    class="border-t border-slate-200/80 pt-4 text-center dark:border-slate-800"
                >
                    <TextLink
                        :href="login()"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-[#0a7f84] underline decoration-[#0a7f84]/30 underline-offset-4 hover:text-[#087078] dark:text-[#5ec9ce] dark:hover:text-[#7ad6d9]"
                    >
                        <ArrowLeft class="size-4 shrink-0" aria-hidden="true" />
                        Voltar para o login
                    </TextLink>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
