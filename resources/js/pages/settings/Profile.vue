<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import CandidateProfileForm from '@/components/Candidate/CandidateProfileForm.vue';
import type { CandidateProfileUser } from '@/components/Candidate/profileTypes';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    isCandidate?: boolean;
    ufs?: string[];
    profile?: CandidateProfileUser | null;
};

withDefaults(defineProps<Props>(), {
    isCandidate: false,
    ufs: () => [],
    profile: null,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head :title="isCandidate ? 'Meu perfil' : 'Configurações de perfil'" />

    <h1 class="sr-only">
        {{ isCandidate ? 'Meu perfil' : 'Configurações de perfil' }}
    </h1>

    <div class="space-y-6">
        <Heading
            v-if="isCandidate && profile"
            variant="small"
            title="Meu perfil"
            description="Atualize seus dados pessoais, documento, endereço e foto. Essas informações são usadas nas inscrições."
        />

        <CandidateProfileForm
            v-if="isCandidate && profile"
            :profile="profile"
            :ufs="ufs"
            :must-verify-email="mustVerifyEmail"
            :status="status"
        />

        <template v-else>
            <section class="space-y-4 rounded-xl border bg-card p-5 shadow-sm">
                <Heading
                    variant="small"
                    title="Editar dados principais"
                    description="Atualize seu nome e endereço de e-mail"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Nome completo</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Nome completo"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Endereço de e-mail</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Seu e-mail"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-2 text-sm text-muted-foreground">
                            Seu endereço de e-mail ainda não foi verificado.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Clique aqui para reenviar o e-mail de
                                verificação.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 rounded-md bg-emerald-100 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            Um novo link de verificação foi enviado para o seu
                            e-mail.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                        >
                            Salvar alterações
                        </Button>
                    </div>
                </Form>
            </section>

            <section
                class="rounded-xl border bg-muted/20 p-4 text-sm text-muted-foreground"
            >
                A exclusão de conta está desabilitada no sistema.
            </section>
        </template>
    </div>
</template>
