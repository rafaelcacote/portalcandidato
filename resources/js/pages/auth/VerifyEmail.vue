<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    CheckCircle2,
    Inbox,
    MailCheck,
    RefreshCw,
    ShieldCheck,
} from 'lucide-vue-next';
import CandidateHeader from '@/components/Candidate/CandidateHeader.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: '',
        description: '',
        contentMaxWidth: 'max-w-xl',
        backdrop: 'login-fundo',
    },
});

const props = defineProps<{
    status?: string;
    email?: string | null;
}>();

const page = usePage();

const displayEmail = computed(
    () => props.email ?? (page.props.auth?.user?.email as string | undefined) ?? '',
);

const linkJustSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <div>
        <Head title="Confirme seu e-mail" />

        <CandidateHeader
            compact
            :show-notice="false"
            class="mb-3 sm:mb-4"
        />

        <Card
            class="overflow-hidden border-border/80 shadow-lg shadow-primary/5 transition-shadow"
        >
            <CardHeader
                class="space-y-4 border-b border-border/60 bg-gradient-to-br from-primary/8 via-transparent to-emerald-500/5 pb-6 pt-6"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/12 text-primary ring-1 ring-primary/20"
                >
                    <MailCheck :size="32" stroke-width="1.75" />
                </div>

                <div class="space-y-2 text-center">
                    <CardTitle class="text-xl font-semibold tracking-tight sm:text-2xl">
                        Confirme seu e-mail
                    </CardTitle>
                    <CardDescription class="text-sm leading-relaxed sm:text-base">
                        Enviamos um link de confirmação para o endereço cadastrado. Abra sua
                        caixa de entrada e clique no botão para liberar o acesso ao portal.
                    </CardDescription>
                </div>
            </CardHeader>

            <CardContent class="space-y-6 pt-6">
                <div
                    v-if="displayEmail"
                    class="flex items-center gap-3 rounded-xl border border-dashed border-primary/30 bg-primary/[0.04] px-4 py-3.5"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Inbox :size="20" />
                    </div>
                    <div class="min-w-0 text-left">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            E-mail cadastrado
                        </p>
                        <p class="truncate text-sm font-semibold text-foreground sm:text-base">
                            {{ displayEmail }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="linkJustSent"
                    class="flex items-start gap-3 rounded-xl border border-emerald-500/25 bg-emerald-500/8 px-4 py-3.5 text-sm text-emerald-800 dark:text-emerald-200"
                    role="status"
                >
                    <CheckCircle2 class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                    <p>
                        Um novo link de confirmação foi enviado. Verifique também a pasta de
                        spam ou promoções.
                    </p>
                </div>

                <ul class="grid gap-3 text-sm text-muted-foreground">
                    <li class="flex items-start gap-2.5">
                        <ShieldCheck class="mt-0.5 size-4 shrink-0 text-primary" />
                        <span>
                            Enquanto o e-mail não for confirmado, o acesso às áreas do portal
                            permanece bloqueado.
                        </span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <RefreshCw class="mt-0.5 size-4 shrink-0 text-primary" />
                        <span>
                            Não recebeu o e-mail? Aguarde alguns minutos ou solicite o reenvio
                            abaixo.
                        </span>
                    </li>
                </ul>

                <Form
                    v-bind="send.form()"
                    class="space-y-4"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        class="h-11 w-full rounded-lg border border-[#39b4b9]/25 bg-[#39b4b9] text-base font-semibold text-white shadow-md transition-transform hover:bg-[#2ea0a6] active:scale-[0.99] focus-visible:border-[#39b4b9]/40 focus-visible:ring-[#39b4b9]/35 dark:border-[#39b4b9]/40 dark:bg-[#39b4b9] dark:hover:bg-[#4dc8cd]"
                        :disabled="processing"
                        data-test="resend-verification-button"
                    >
                        <Spinner v-if="processing" />
                        Reenviar e-mail de confirmação
                    </Button>
                </Form>

                <div class="border-t border-border/60 pt-4 text-center">
                    <TextLink
                        :href="logout()"
                        as="button"
                        class="text-sm text-muted-foreground underline-offset-4 hover:text-foreground"
                    >
                        Sair e usar outro e-mail
                    </TextLink>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
