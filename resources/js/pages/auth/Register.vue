<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Cadastro de candidato',
        description: 'Preencha seus dados para criar sua conta no portal',
    },
});

const props = defineProps<{
    ufs: string[];
}>();

const selectClass: HTMLAttributes['class'] = cn(
    'border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none md:text-sm',
    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    data_nascimento: '',
    cpf: '',
    identidade: '',
    orgao_emissor: '',
    identidade_uf: '',
    identidade_data_emissao: '',
    naturalidade: '',
    nacionalidade: 'Brasileira',
    sexo: '',
    endereco: '',
    endereco_numero: '',
    bairro: '',
    cep: '',
    cidade: '',
    endereco_uf: '',
    pais: 'Brasil',
    telefone: '',
    telefone_fixo: '',
    foto: null as File | null,
});

function onFotoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.foto = input.files?.[0] ?? null;
}

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <Head title="Cadastro" />

    <form class="flex flex-col gap-8" @submit.prevent="submit">
        <section class="grid gap-4">
            <h2 class="text-lg font-semibold tracking-tight">Documento com foto</h2>
            <div class="grid gap-2">
                <Label for="foto">Sua foto</Label>
                <input
                    id="foto"
                    type="file"
                    name="foto"
                    accept="image/jpeg,image/png,image/webp"
                    :class="cn(selectClass, 'py-2')"
                    @change="onFotoChange"
                />
                <p class="text-muted-foreground text-xs">
                    Opcional no cadastro. Formatos: JPG, PNG ou Webp. Máximo 5&nbsp;MB.
                </p>
                <InputError :message="form.errors.foto" />
            </div>
        </section>

        <section class="grid gap-4">
            <h2 class="text-lg font-semibold tracking-tight">Dados pessoais</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="name">Nome completo</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autocomplete="name"
                        name="name"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="data_nascimento">Data de nascimento</Label>
                    <Input
                        id="data_nascimento"
                        v-model="form.data_nascimento"
                        type="date"
                        required
                        name="data_nascimento"
                    />
                    <InputError :message="form.errors.data_nascimento" />
                </div>
                <div class="grid gap-2">
                    <Label for="cpf">CPF</Label>
                    <Input
                        id="cpf"
                        v-model="form.cpf"
                        type="text"
                        required
                        inputmode="numeric"
                        autocomplete="off"
                        name="cpf"
                        placeholder="Somente números"
                    />
                    <InputError :message="form.errors.cpf" />
                </div>
                <div class="grid gap-2">
                    <Label for="identidade">Identidade (RG)</Label>
                    <Input
                        id="identidade"
                        v-model="form.identidade"
                        type="text"
                        required
                        name="identidade"
                    />
                    <InputError :message="form.errors.identidade" />
                </div>
                <div class="grid gap-2">
                    <Label for="orgao_emissor">Órgão emissor</Label>
                    <Input
                        id="orgao_emissor"
                        v-model="form.orgao_emissor"
                        type="text"
                        required
                        name="orgao_emissor"
                    />
                    <InputError :message="form.errors.orgao_emissor" />
                </div>
                <div class="grid gap-2">
                    <Label for="identidade_uf">UF (identidade)</Label>
                    <select
                        id="identidade_uf"
                        v-model="form.identidade_uf"
                        required
                        name="identidade_uf"
                        :class="selectClass"
                    >
                        <option disabled value="">Selecione</option>
                        <option v-for="uf in props.ufs" :key="`id-${uf}`" :value="uf">
                            {{ uf }}
                        </option>
                    </select>
                    <InputError :message="form.errors.identidade_uf" />
                </div>
                <div class="grid gap-2">
                    <Label for="identidade_data_emissao">Data de emissão (RG)</Label>
                    <Input
                        id="identidade_data_emissao"
                        v-model="form.identidade_data_emissao"
                        type="date"
                        required
                        name="identidade_data_emissao"
                    />
                    <InputError :message="form.errors.identidade_data_emissao" />
                </div>
                <div class="grid gap-2">
                    <Label for="naturalidade">Naturalidade</Label>
                    <Input
                        id="naturalidade"
                        v-model="form.naturalidade"
                        type="text"
                        required
                        name="naturalidade"
                    />
                    <InputError :message="form.errors.naturalidade" />
                </div>
                <div class="grid gap-2">
                    <Label for="nacionalidade">Nacionalidade</Label>
                    <Input
                        id="nacionalidade"
                        v-model="form.nacionalidade"
                        type="text"
                        required
                        name="nacionalidade"
                    />
                    <InputError :message="form.errors.nacionalidade" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="sexo">Sexo</Label>
                    <select
                        id="sexo"
                        v-model="form.sexo"
                        required
                        name="sexo"
                        :class="selectClass"
                    >
                        <option disabled value="">Selecione</option>
                        <option value="masculino">Masculino</option>
                        <option value="feminino">Feminino</option>
                        <option value="outro">Outro</option>
                        <option value="prefiro_nao_informar">Prefiro não informar</option>
                    </select>
                    <InputError :message="form.errors.sexo" />
                </div>
            </div>
        </section>

        <section class="grid gap-4">
            <h2 class="text-lg font-semibold tracking-tight">Endereço residencial</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="endereco">Endereço (logradouro)</Label>
                    <Input
                        id="endereco"
                        v-model="form.endereco"
                        type="text"
                        required
                        name="endereco"
                    />
                    <InputError :message="form.errors.endereco" />
                </div>
                <div class="grid gap-2">
                    <Label for="endereco_numero">Número</Label>
                    <Input
                        id="endereco_numero"
                        v-model="form.endereco_numero"
                        type="text"
                        required
                        name="endereco_numero"
                    />
                    <InputError :message="form.errors.endereco_numero" />
                </div>
                <div class="grid gap-2">
                    <Label for="bairro">Bairro</Label>
                    <Input id="bairro" v-model="form.bairro" type="text" required name="bairro" />
                    <InputError :message="form.errors.bairro" />
                </div>
                <div class="grid gap-2">
                    <Label for="cep">CEP</Label>
                    <Input
                        id="cep"
                        v-model="form.cep"
                        type="text"
                        required
                        name="cep"
                        placeholder="00000-000 ou somente números"
                    />
                    <InputError :message="form.errors.cep" />
                </div>
                <div class="grid gap-2">
                    <Label for="cidade">Cidade</Label>
                    <Input id="cidade" v-model="form.cidade" type="text" required name="cidade" />
                    <InputError :message="form.errors.cidade" />
                </div>
                <div class="grid gap-2">
                    <Label for="endereco_uf">UF</Label>
                    <select
                        id="endereco_uf"
                        v-model="form.endereco_uf"
                        required
                        name="endereco_uf"
                        :class="selectClass"
                    >
                        <option disabled value="">Selecione</option>
                        <option v-for="uf in props.ufs" :key="`ed-${uf}`" :value="uf">
                            {{ uf }}
                        </option>
                    </select>
                    <InputError :message="form.errors.endereco_uf" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="pais">País</Label>
                    <Input id="pais" v-model="form.pais" type="text" required name="pais" />
                    <InputError :message="form.errors.pais" />
                </div>
            </div>
        </section>

        <section class="grid gap-4">
            <h2 class="text-lg font-semibold tracking-tight">Contato e acesso</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="telefone_fixo">Telefone fixo</Label>
                    <Input
                        id="telefone_fixo"
                        v-model="form.telefone_fixo"
                        type="text"
                        name="telefone_fixo"
                    />
                    <InputError :message="form.errors.telefone_fixo" />
                </div>
                <div class="grid gap-2">
                    <Label for="telefone">Celular</Label>
                    <Input
                        id="telefone"
                        v-model="form.telefone"
                        type="text"
                        required
                        name="telefone"
                        autocomplete="tel"
                    />
                    <InputError :message="form.errors.telefone" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="email">E-mail</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        name="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="password">Senha</Label>
                    <PasswordInput
                        id="password"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        name="password"
                    />
                    <InputError :message="form.errors.password" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="password_confirmation">Confirmar senha</Label>
                    <PasswordInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        name="password_confirmation"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </div>
        </section>

        <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
            data-test="register-user-button"
        >
            <Spinner v-if="form.processing" />
            Criar conta
        </Button>

        <div class="text-center text-sm text-muted-foreground">
            Já tem conta?
            <TextLink :href="login()" class="underline underline-offset-4">Entrar</TextLink>
        </div>
    </form>
</template>
