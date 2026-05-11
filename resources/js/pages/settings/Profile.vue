<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const profileSections = computed(() => [
    {
        title: 'Contato',
        fields: [
            { label: 'E-mail', value: user.value.email },
            { label: 'Telefone celular', value: (user.value as any).telefone },
            { label: 'Telefone fixo', value: (user.value as any).telefone_fixo },
        ],
    },
    {
        title: 'Dados pessoais',
        fields: [
            { label: 'CPF', value: (user.value as any).cpf },
            { label: 'Data de nascimento', value: (user.value as any).data_nascimento },
            { label: 'Naturalidade', value: (user.value as any).naturalidade },
            { label: 'Nacionalidade', value: (user.value as any).nacionalidade },
            { label: 'Sexo', value: (user.value as any).sexo },
        ],
    },
    {
        title: 'Documento de identificação',
        fields: [
            { label: 'Identidade', value: (user.value as any).identidade },
            { label: 'Órgão emissor', value: (user.value as any).orgao_emissor },
            { label: 'UF da identidade', value: (user.value as any).identidade_uf },
            { label: 'Data de emissão', value: (user.value as any).identidade_data_emissao },
        ],
    },
    {
        title: 'Endereço',
        fields: [
            { label: 'Endereço', value: (user.value as any).endereco },
            { label: 'Número', value: (user.value as any).endereco_numero },
            { label: 'Bairro', value: (user.value as any).bairro },
            { label: 'CEP', value: (user.value as any).cep },
            { label: 'Cidade', value: (user.value as any).cidade },
            { label: 'UF', value: (user.value as any).endereco_uf },
            { label: 'País', value: (user.value as any).pais },
        ],
    },
]);

const userAvatar = computed(() => (user.value as any).avatar ?? null);

const displayValue = (value: unknown): string => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return String(value);
};
</script>

<template>
    <Head title="Configurações de perfil" />

    <h1 class="sr-only">Configurações de perfil</h1>

    <div class="space-y-8">
        <section class="rounded-xl border bg-card p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Avatar class="size-12 overflow-hidden rounded-xl">
                        <AvatarImage v-if="userAvatar" :src="userAvatar" :alt="user.name" />
                        <AvatarFallback class="rounded-xl bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0">
                        <p class="truncate text-base font-semibold">{{ user.name }}</p>
                        <p class="truncate text-sm text-muted-foreground">{{ user.email }}</p>
                    </div>
                </div>
                <span
                    class="inline-flex h-7 items-center rounded-full px-3 text-xs font-medium"
                    :class="
                        user.email_verified_at
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300'
                    "
                >
                    {{ user.email_verified_at ? 'E-mail verificado' : 'E-mail pendente de verificação' }}
                </span>
            </div>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Perfil completo"
                description="Confira os dados da sua conta organizados por categoria"
            />

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <article
                    v-for="section in profileSections"
                    :key="section.title"
                    class="rounded-xl border bg-card p-4 shadow-sm"
                >
                    <h3 class="mb-3 text-sm font-semibold text-foreground">
                        {{ section.title }}
                    </h3>

                    <dl class="space-y-3">
                        <div
                            v-for="field in section.fields"
                            :key="field.label"
                            class="grid grid-cols-1 gap-1 border-b border-dashed border-border pb-2 last:border-b-0 last:pb-0 sm:grid-cols-[10rem_1fr]"
                        >
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ field.label }}
                            </dt>
                            <dd class="truncate text-sm text-foreground">
                                {{ displayValue(field.value) }}
                            </dd>
                        </div>
                    </dl>
                </article>
            </div>
        </section>

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
                            Clique aqui para reenviar o e-mail de verificação.
                        </Link>
                    </p>

                    <div
                        v-if="status === 'verification-link-sent'"
                        class="mt-2 rounded-md bg-emerald-100 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                    >
                        Um novo link de verificação foi enviado para o seu e-mail.
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing" data-test="update-profile-button">
                        Salvar alterações
                    </Button>
                </div>
            </Form>
        </section>

        <section class="rounded-xl border bg-muted/20 p-4 text-sm text-muted-foreground">
            A exclusão de conta está desabilitada no sistema.
        </section>
    </div>
</template>
