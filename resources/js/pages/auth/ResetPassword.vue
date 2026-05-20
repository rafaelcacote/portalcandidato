<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { KeyRound, Mail } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: '',
        description: '',
        contentMaxWidth: 'max-w-md',
        backdrop: 'login-fundo',
    },
});

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <div>
        <Head title="Redefinir senha" />

        <Card
            class="overflow-hidden border-slate-200/90 bg-white shadow-xl shadow-slate-900/[0.06] dark:border-slate-800 dark:bg-slate-950"
        >
            <CardHeader
                class="space-y-4 border-b border-slate-200/80 bg-gradient-to-br from-[#39b4b9]/10 via-transparent to-emerald-500/5 pb-6 pt-6 dark:border-slate-800"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#39b4b9]/12 text-[#0a7f84] ring-1 ring-[#39b4b9]/25 dark:text-[#7ad6d9]"
                >
                    <KeyRound :size="32" stroke-width="1.75" />
                </div>

                <div class="space-y-2 text-center">
                    <CardTitle class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-2xl">
                        Nova senha
                    </CardTitle>
                    <CardDescription class="text-sm leading-relaxed text-slate-600 dark:text-slate-400 sm:text-base">
                        Escolha uma senha segura para acessar o portal do candidato.
                    </CardDescription>
                </div>
            </CardHeader>

            <CardContent class="pt-6">
                <Form
                    v-bind="update.form()"
                    :transform="(data) => ({ ...data, token, email })"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="space-y-5"
                >
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
                                autocomplete="email"
                                v-model="inputEmail"
                                readonly
                                class="h-12 cursor-not-allowed border-slate-200 bg-slate-50 pl-10 text-base text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 sm:h-11 md:text-sm"
                            />
                        </div>
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password" class="text-slate-900 dark:text-slate-100">Nova senha</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            autofocus
                            placeholder="Digite sua nova senha"
                            class="h-12 border-slate-200 bg-white text-base shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:h-11 md:text-sm"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation" class="text-slate-900 dark:text-slate-100">
                            Confirmar nova senha
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="Repita a nova senha"
                            class="h-12 border-slate-200 bg-white text-base shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:h-11 md:text-sm"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <Button
                        type="submit"
                        class="h-12 w-full rounded-xl border border-[#39b4b9]/25 bg-[#39b4b9] text-base font-semibold text-white shadow-md transition-transform hover:bg-[#2ea0a6] active:scale-[0.99] focus-visible:border-[#39b4b9]/40 focus-visible:ring-[#39b4b9]/35 sm:h-11 dark:border-[#39b4b9]/40 dark:bg-[#39b4b9] dark:hover:bg-[#4dc8cd]"
                        :disabled="processing"
                        data-test="reset-password-button"
                    >
                        <Spinner v-if="processing" />
                        Redefinir senha
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
