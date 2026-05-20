<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import type { HTMLAttributes } from 'vue';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import {
    BookUser,
    Camera,
    Loader2,
    Mail,
    MapPin,
} from 'lucide-vue-next';
import CandidateHeader from '@/components/Candidate/CandidateHeader.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { cepDigitsOnly, cpfDigitsOnly, formatCepDisplay, isValidCpfDigits } from '@/lib/brDocuments';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: '',
        description: '',
        contentMaxWidth: 'max-w-3xl',
    },
});

const props = defineProps<{
    ufs: string[];
}>();

const CHECK_CPF_URL = '/register/check-cpf';

const selectClass: HTMLAttributes['class'] = cn(
    'border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none md:text-sm',
    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
    'disabled:cursor-not-allowed disabled:opacity-50',
);

const OPTIONAL_FIELDS = new Set(['telefone_fixo']);

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

const blurTouched = ref<Record<string, boolean>>({});
const submitAttempted = ref(false);

const cpfCheckStatus = ref<'idle' | 'loading' | 'available' | 'taken' | 'invalid'>('idle');
const cepLookupLoading = ref(false);
const cepLookupMessage = ref<string | null>(null);
const fotoPreviewUrl = ref<string | null>(null);

function revokeFotoPreview(): void {
    if (fotoPreviewUrl.value !== null) {
        URL.revokeObjectURL(fotoPreviewUrl.value);
        fotoPreviewUrl.value = null;
    }
}

onUnmounted(revokeFotoPreview);

function touch(name: string): void {
    blurTouched.value = { ...blurTouched.value, [name]: true };
}

function fieldValue(name: string): string | File | null {
    const record = form as unknown as Record<string, unknown>;
    const v = record[name];

    if (v === undefined) {
        return null;
    }

    if (typeof v === 'string' || v instanceof File) {
        return v;
    }

    return null;
}

function isRequiredEmpty(name: string): boolean {
    if (OPTIONAL_FIELDS.has(name)) {
        return false;
    }

    const v = fieldValue(name);
    if (v === null || v === undefined) {
        return true;
    }
    if (typeof v === 'string') {
        return v.trim() === '';
    }
    if (v instanceof File) {
        return false;
    }

    return false;
}

function fieldInvalid(name: string): boolean {
    const formErrors = form.errors as Record<string, string | undefined>;

    if (formErrors[name]) {
        return true;
    }

    if (name === 'cpf') {
        if (cpfCheckStatus.value === 'taken' || cpfCheckStatus.value === 'invalid') {
            return true;
        }
    }

    const show = blurTouched.value[name] === true || submitAttempted.value;

    return show && isRequiredEmpty(name);
}

function inputInvalidClass(name: string): string {
    return fieldInvalid(name)
        ? 'border-destructive focus-visible:border-destructive focus-visible:ring-destructive/30'
        : '';
}

function selectInvalidClass(name: string): string {
    return fieldInvalid(name)
        ? 'border-destructive focus-visible:border-destructive focus-visible:ring-destructive/30'
        : '';
}

function onFotoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    revokeFotoPreview();
    form.foto = file;
    form.clearErrors('foto');
    if (file !== null) {
        fotoPreviewUrl.value = URL.createObjectURL(file);
    }
    touch('foto');
}

function onCpfInput(value: string | number): void {
    form.cpf = cpfDigitsOnly(String(value)).slice(0, 11);
    form.clearErrors('cpf');
    cpfCheckStatus.value = 'idle';
}

function onCepInput(value: string | number): void {
    form.cep = cepDigitsOnly(String(value));
    form.clearErrors('cep');
    cepLookupMessage.value = null;
    debouncedCepLookup();
}

const debouncedCheckCpf = useDebounceFn(async (): Promise<void> => {
    const digits = cpfDigitsOnly(form.cpf);
    if (digits.length !== 11) {
        cpfCheckStatus.value = 'idle';

        return;
    }

    if (!isValidCpfDigits(digits)) {
        cpfCheckStatus.value = 'invalid';

        return;
    }

    cpfCheckStatus.value = 'loading';
    try {
        const res = await fetch(`${CHECK_CPF_URL}?cpf=${encodeURIComponent(digits)}`, {
            headers: { Accept: 'application/json' },
        });
        const data = (await res.json()) as { status?: string };
        if (data.status === 'available') {
            cpfCheckStatus.value = 'available';
        } else if (data.status === 'taken') {
            cpfCheckStatus.value = 'taken';
        } else if (data.status === 'invalid') {
            cpfCheckStatus.value = 'invalid';
        } else {
            cpfCheckStatus.value = 'idle';
        }
    } catch {
        cpfCheckStatus.value = 'idle';
    }
}, 450);

watch(
    () => form.cpf,
    () => {
        void debouncedCheckCpf();
    },
);

type ViaCepResponse = {
    /** ViaCEP pode retornar boolean ou string `"true"` quando o CEP não existe. */
    erro?: boolean | string;
    logradouro?: string;
    bairro?: string;
    localidade?: string;
    uf?: string;
};

function isViaCepNotFound(data: ViaCepResponse): boolean {
    if (data.erro === true) {
        return true;
    }

    if (typeof data.erro === 'string' && data.erro.toLowerCase() === 'true') {
        return true;
    }

    return false;
}

async function focusAndSelectCepField(): Promise<void> {
    await nextTick();
    const el = document.getElementById('cep');
    if (el instanceof HTMLInputElement) {
        el.focus();
        el.select();
    }
}

async function lookupCep(): Promise<void> {
    const digits = cepDigitsOnly(form.cep);
    if (digits.length !== 8) {
        return;
    }

    cepLookupLoading.value = true;
    cepLookupMessage.value = null;
    try {
        const res = await fetch(`https://viacep.com.br/ws/${digits}/json/`);
        const data = (await res.json()) as ViaCepResponse;
        if (isViaCepNotFound(data)) {
            cepLookupMessage.value = 'CEP não encontrado. Verifique os dígitos.';
            await focusAndSelectCepField();

            return;
        }

        form.endereco = data.logradouro ?? '';
        form.bairro = data.bairro ?? '';
        form.cidade = data.localidade ?? '';
        if (data.uf && props.ufs.includes(data.uf)) {
            form.endereco_uf = data.uf;
        }
        form.pais = 'Brasil';
        cepLookupMessage.value = 'Endereço preenchido a partir do CEP.';
        touch('endereco');
        touch('bairro');
        touch('cidade');
        touch('endereco_uf');
        await nextTick();
        document.getElementById('endereco_numero')?.focus();
    } catch {
        cepLookupMessage.value = 'Não foi possível consultar o CEP. Tente novamente.';
        await focusAndSelectCepField();
    } finally {
        cepLookupLoading.value = false;
    }
}

const debouncedCepLookup = useDebounceFn(lookupCep, 500);

function submit(): void {
    submitAttempted.value = true;
    if (cpfCheckStatus.value === 'taken' || cpfCheckStatus.value === 'invalid') {
        return;
    }
    form.post(store.url());
}

const cpfHint = computed((): string | null => {
    if (cpfCheckStatus.value === 'loading') {
        return 'Verificando CPF…';
    }
    if (cpfCheckStatus.value === 'taken') {
        return 'Este CPF já possui cadastro.';
    }
    if (cpfCheckStatus.value === 'invalid') {
        return 'CPF inválido. Confira os números.';
    }
    if (cpfCheckStatus.value === 'available') {
        return 'CPF disponível para cadastro.';
    }

    return null;
});
</script>

<template>
    <div>
        <Head title="Cadastro" />

        <CandidateHeader class="mb-5 sm:mb-6" />

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <!-- Foto -->
            <Card class="overflow-hidden border-border/80 shadow-sm transition-shadow hover:shadow-md">
                <CardContent class="space-y-1 pt-2">
                    <div class="grid gap-2">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Camera :size="18" />
                            </div>
                            <Label for="foto" class="leading-none">Sua foto *</Label>
                        </div>
                        <div
                            v-if="fotoPreviewUrl"
                            class="flex items-center gap-4 rounded-lg border border-border/60 bg-muted/20 p-3"
                        >
                            <img
                                :src="fotoPreviewUrl"
                                alt="Pré-visualização da foto"
                                class="size-24 shrink-0 rounded-lg border border-border object-cover shadow-sm"
                            />
                            <p class="text-sm text-muted-foreground">
                                Pré-visualização da foto selecionada. Você pode escolher outro
                                arquivo abaixo, se preferir.
                            </p>
                        </div>
                        <input
                            id="foto"
                            type="file"
                            name="foto"
                            accept="image/jpeg,image/png,image/webp"
                            :class="
                                cn(
                                    selectClass,
                                    inputInvalidClass('foto'),
                                    'py-2 file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-foreground',
                                )
                            "
                            :aria-invalid="fieldInvalid('foto')"
                            @change="onFotoChange"
                            @blur="touch('foto')"
                        />
                        <p class="text-xs text-muted-foreground">
                            Obrigatório · JPG, PNG ou WebP · máximo 5&nbsp;MB.
                        </p>
                        <InputError :message="form.errors.foto" />
                    </div>
                </CardContent>
            </Card>

            <!-- Dados pessoais -->
            <Card class="overflow-hidden border-border/80 shadow-sm transition-shadow hover:shadow-md">
                <CardHeader class="space-y-1 border-b border-border/60 bg-muted/30 pb-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <BookUser :size="18" />
                        </div>
                        <div>
                            <CardTitle class="text-base">Dados pessoais</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Identidade e informações civis conforme documento oficial.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5 pt-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="name">Nome completo *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                name="name"
                                :class="inputInvalidClass('name')"
                                :aria-invalid="fieldInvalid('name')"
                                @blur="touch('name')"
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="data_nascimento">Data de nascimento *</Label>
                            <Input
                                id="data_nascimento"
                                v-model="form.data_nascimento"
                                type="date"
                                name="data_nascimento"
                                :class="inputInvalidClass('data_nascimento')"
                                :aria-invalid="fieldInvalid('data_nascimento')"
                                @blur="touch('data_nascimento')"
                            />
                            <InputError :message="form.errors.data_nascimento" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="cpf">CPF *</Label>
                            <div class="relative">
                                <Input
                                    id="cpf"
                                    :model-value="form.cpf"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    name="cpf"
                                    maxlength="11"
                                    placeholder="Somente números (11 dígitos)"
                                    :class="cn('pr-10 tabular-nums', inputInvalidClass('cpf'))"
                                    :aria-invalid="fieldInvalid('cpf')"
                                    @update:model-value="onCpfInput"
                                    @blur="touch('cpf')"
                                />
                                <div
                                    class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground"
                                >
                                    <Loader2
                                        v-if="cpfCheckStatus === 'loading'"
                                        :size="16"
                                        class="animate-spin"
                                    />
                                </div>
                            </div>
                            <p
                                v-if="cpfHint"
                                class="text-xs"
                                :class="
                                    cpfCheckStatus === 'available'
                                        ? 'text-green-600 dark:text-green-400'
                                        : cpfCheckStatus === 'taken' || cpfCheckStatus === 'invalid'
                                          ? 'text-destructive'
                                          : 'text-muted-foreground'
                                "
                            >
                                {{ cpfHint }}
                            </p>
                            <InputError :message="form.errors.cpf" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="identidade">Identidade (RG) *</Label>
                            <Input
                                id="identidade"
                                v-model="form.identidade"
                                type="text"
                                name="identidade"
                                :class="inputInvalidClass('identidade')"
                                :aria-invalid="fieldInvalid('identidade')"
                                @blur="touch('identidade')"
                            />
                            <InputError :message="form.errors.identidade" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="orgao_emissor">Órgão emissor *</Label>
                            <Input
                                id="orgao_emissor"
                                v-model="form.orgao_emissor"
                                type="text"
                                name="orgao_emissor"
                                :class="inputInvalidClass('orgao_emissor')"
                                :aria-invalid="fieldInvalid('orgao_emissor')"
                                @blur="touch('orgao_emissor')"
                            />
                            <InputError :message="form.errors.orgao_emissor" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="identidade_uf">UF (identidade) *</Label>
                            <select
                                id="identidade_uf"
                                v-model="form.identidade_uf"
                                name="identidade_uf"
                                :class="cn(selectClass, selectInvalidClass('identidade_uf'))"
                                :aria-invalid="fieldInvalid('identidade_uf')"
                                @blur="touch('identidade_uf')"
                            >
                                <option disabled value="">Selecione</option>
                                <option v-for="uf in props.ufs" :key="`id-${uf}`" :value="uf">
                                    {{ uf }}
                                </option>
                            </select>
                            <InputError :message="form.errors.identidade_uf" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="identidade_data_emissao">Data de emissão (RG) *</Label>
                            <Input
                                id="identidade_data_emissao"
                                v-model="form.identidade_data_emissao"
                                type="date"
                                name="identidade_data_emissao"
                                :class="inputInvalidClass('identidade_data_emissao')"
                                :aria-invalid="fieldInvalid('identidade_data_emissao')"
                                @blur="touch('identidade_data_emissao')"
                            />
                            <InputError :message="form.errors.identidade_data_emissao" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="naturalidade">Naturalidade *</Label>
                            <Input
                                id="naturalidade"
                                v-model="form.naturalidade"
                                type="text"
                                name="naturalidade"
                                :class="inputInvalidClass('naturalidade')"
                                :aria-invalid="fieldInvalid('naturalidade')"
                                @blur="touch('naturalidade')"
                            />
                            <InputError :message="form.errors.naturalidade" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="nacionalidade">Nacionalidade *</Label>
                            <Input
                                id="nacionalidade"
                                v-model="form.nacionalidade"
                                type="text"
                                name="nacionalidade"
                                :class="inputInvalidClass('nacionalidade')"
                                :aria-invalid="fieldInvalid('nacionalidade')"
                                @blur="touch('nacionalidade')"
                            />
                            <InputError :message="form.errors.nacionalidade" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="sexo">Sexo *</Label>
                            <select
                                id="sexo"
                                v-model="form.sexo"
                                name="sexo"
                                :class="cn(selectClass, selectInvalidClass('sexo'))"
                                :aria-invalid="fieldInvalid('sexo')"
                                @blur="touch('sexo')"
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
                </CardContent>
            </Card>

            <!-- Endereço — CEP primeiro -->
            <Card class="overflow-hidden border-border/80 shadow-sm transition-shadow hover:shadow-md">
                <CardHeader class="space-y-1 border-b border-border/60 bg-muted/30 pb-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <MapPin :size="18" />
                        </div>
                        <div>
                            <CardTitle class="text-base">Endereço residencial</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Digite o CEP primeiro — buscamos logradouro, bairro, cidade e UF
                                automaticamente.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5 pt-5">
                    <div
                        class="rounded-xl border border-dashed border-primary/25 bg-primary/[0.03] p-4 sm:p-5"
                    >
                        <div class="grid gap-2">
                            <Label for="cep">CEP *</Label>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                <div class="relative min-w-0 flex-1">
                                    <Input
                                        id="cep"
                                        :model-value="formatCepDisplay(form.cep)"
                                        type="text"
                                        inputmode="numeric"
                                        name="cep"
                                        maxlength="9"
                                        placeholder="00000-000"
                                        autocomplete="postal-code"
                                        :class="cn('pr-10 font-mono tabular-nums', inputInvalidClass('cep'))"
                                        :aria-invalid="fieldInvalid('cep')"
                                        @update:model-value="onCepInput"
                                        @blur="touch('cep')"
                                    />
                                    <div
                                        class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground"
                                    >
                                        <Loader2
                                            v-if="cepLookupLoading"
                                            :size="16"
                                            class="animate-spin"
                                        />
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    class="h-9 w-full shrink-0 sm:w-auto"
                                    :disabled="cepDigitsOnly(form.cep).length !== 8 || cepLookupLoading"
                                    @click="lookupCep"
                                >
                                    Buscar CEP
                                </Button>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Ao completar 8 dígitos, o endereço é buscado automaticamente.
                            </p>
                            <p
                                v-if="cepLookupMessage"
                                class="text-xs font-medium text-primary"
                            >
                                {{ cepLookupMessage }}
                            </p>
                            <InputError :message="form.errors.cep" />
                        </div>
                    </div>

                    <Separator />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="endereco">Endereço (logradouro) *</Label>
                            <Input
                                id="endereco"
                                v-model="form.endereco"
                                type="text"
                                name="endereco"
                                autocomplete="street-address"
                                :class="inputInvalidClass('endereco')"
                                :aria-invalid="fieldInvalid('endereco')"
                                @blur="touch('endereco')"
                            />
                            <InputError :message="form.errors.endereco" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="endereco_numero">Número *</Label>
                            <Input
                                id="endereco_numero"
                                v-model="form.endereco_numero"
                                type="text"
                                name="endereco_numero"
                                :class="inputInvalidClass('endereco_numero')"
                                :aria-invalid="fieldInvalid('endereco_numero')"
                                @blur="touch('endereco_numero')"
                            />
                            <InputError :message="form.errors.endereco_numero" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="bairro">Bairro *</Label>
                            <Input
                                id="bairro"
                                v-model="form.bairro"
                                type="text"
                                name="bairro"
                                :class="inputInvalidClass('bairro')"
                                :aria-invalid="fieldInvalid('bairro')"
                                @blur="touch('bairro')"
                            />
                            <InputError :message="form.errors.bairro" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="cidade">Cidade *</Label>
                            <Input
                                id="cidade"
                                v-model="form.cidade"
                                type="text"
                                name="cidade"
                                :class="inputInvalidClass('cidade')"
                                :aria-invalid="fieldInvalid('cidade')"
                                @blur="touch('cidade')"
                            />
                            <InputError :message="form.errors.cidade" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="endereco_uf">UF *</Label>
                            <select
                                id="endereco_uf"
                                v-model="form.endereco_uf"
                                name="endereco_uf"
                                :class="cn(selectClass, selectInvalidClass('endereco_uf'))"
                                :aria-invalid="fieldInvalid('endereco_uf')"
                                @blur="touch('endereco_uf')"
                            >
                                <option disabled value="">Selecione</option>
                                <option v-for="uf in props.ufs" :key="`ed-${uf}`" :value="uf">
                                    {{ uf }}
                                </option>
                            </select>
                            <InputError :message="form.errors.endereco_uf" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="pais">País *</Label>
                            <Input
                                id="pais"
                                v-model="form.pais"
                                type="text"
                                name="pais"
                                :class="inputInvalidClass('pais')"
                                :aria-invalid="fieldInvalid('pais')"
                                @blur="touch('pais')"
                            />
                            <InputError :message="form.errors.pais" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Contato -->
            <Card class="overflow-hidden border-border/80 shadow-sm transition-shadow hover:shadow-md">
                <CardHeader class="space-y-1 border-b border-border/60 bg-muted/30 pb-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Mail :size="18" />
                        </div>
                        <div>
                            <CardTitle class="text-base">Contato e acesso</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                E-mail para login e senha forte para proteger sua conta.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5 pt-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="telefone_fixo">Telefone fixo</Label>
                            <Input
                                id="telefone_fixo"
                                v-model="form.telefone_fixo"
                                type="text"
                                name="telefone_fixo"
                                @blur="touch('telefone_fixo')"
                            />
                            <InputError :message="form.errors.telefone_fixo" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="telefone">Celular *</Label>
                            <Input
                                id="telefone"
                                v-model="form.telefone"
                                type="text"
                                name="telefone"
                                autocomplete="tel"
                                :class="inputInvalidClass('telefone')"
                                :aria-invalid="fieldInvalid('telefone')"
                                @blur="touch('telefone')"
                            />
                            <InputError :message="form.errors.telefone" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="email">E-mail *</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                name="email"
                                :class="inputInvalidClass('email')"
                                :aria-invalid="fieldInvalid('email')"
                                @blur="touch('email')"
                            />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <div class="flex items-center gap-2">
                                <Shield :size="14" class="shrink-0 text-muted-foreground" />
                                <Label for="password">Senha *</Label>
                            </div>
                            <PasswordInput
                                id="password"
                                v-model="form.password"
                                autocomplete="new-password"
                                name="password"
                                :class="inputInvalidClass('password')"
                                :aria-invalid="fieldInvalid('password')"
                                @blur="touch('password')"
                            />
                            <InputError :message="form.errors.password" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="password_confirmation">Confirmar senha *</Label>
                            <PasswordInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                autocomplete="new-password"
                                name="password_confirmation"
                                :class="inputInvalidClass('password_confirmation')"
                                :aria-invalid="fieldInvalid('password_confirmation')"
                                @blur="touch('password_confirmation')"
                            />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Button
                type="submit"
                class="h-11 w-full rounded-lg border border-[#39b4b9]/25 bg-[#39b4b9] text-base font-semibold text-white shadow-md transition-transform hover:bg-[#2ea0a6] active:scale-[0.99] focus-visible:border-[#39b4b9]/40 focus-visible:ring-[#39b4b9]/35 dark:border-[#39b4b9]/40 dark:bg-[#39b4b9] dark:hover:bg-[#4dc8cd]"
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
    </div>
</template>
