<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import type { HTMLAttributes } from 'vue';
import { computed, nextTick, onUnmounted, ref } from 'vue';
import {
    BookUser,
    Camera,
    CheckCircle2,
    Loader2,
    Lock,
    Mail,
    MapPin,
    Phone,
    UploadCloud,
} from 'lucide-vue-next';
import CandidateProfileCompletion from '@/components/Candidate/CandidateProfileCompletion.vue';
import LgpdDataProtectionNotice from '@/components/Lgpd/LgpdDataProtectionNotice.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import {
    getProfileCompletion,
    normalizeSexo,
    toDateInputValue,
    type CandidateProfileUser,
} from '@/components/Candidate/profileTypes';
import { getInitials } from '@/composables/useInitials';
import { cepDigitsOnly, formatCepDisplay, formatCpfDisplay } from '@/lib/brDocuments';
import { normalizeUploadFile } from '@/lib/uploadFile';
import { cn } from '@/lib/utils';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { send } from '@/routes/verification';

const props = withDefaults(
    defineProps<{
        profile: CandidateProfileUser;
        ufs: string[];
        mustVerifyEmail: boolean;
        status?: string;
        /** Exibe formulário embutido na inscrição (sem navegação de seções). */
        embedded?: boolean;
        /** Prefixo para ids de campos quando há mais de um formulário na página. */
        idPrefix?: string;
        /** Etapa da inscrição a restaurar após salvar (modo embutido). */
        enrollmentStep?: number;
    }>(),
    {
        embedded: false,
        idPrefix: '',
        enrollmentStep: 2,
    },
);

const emit = defineEmits<{
    saved: [];
    cancel: [];
}>();

function fieldId(name: string): string {
    return props.idPrefix ? `${props.idPrefix}${name}` : name;
}

const selectClass: HTMLAttributes['class'] = cn(
    'border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-base text-foreground shadow-xs outline-none [color-scheme:light] dark:[color-scheme:dark] md:text-sm',
    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
    'disabled:cursor-not-allowed disabled:opacity-50',
);

const form = useForm({
    name: props.profile.name ?? '',
    email: props.profile.email ?? '',
    data_nascimento: toDateInputValue(props.profile.data_nascimento),
    identidade: props.profile.identidade ?? '',
    orgao_emissor: props.profile.orgao_emissor ?? '',
    identidade_uf: props.profile.identidade_uf ?? '',
    identidade_data_emissao: toDateInputValue(props.profile.identidade_data_emissao),
    naturalidade: props.profile.naturalidade ?? '',
    nacionalidade: props.profile.nacionalidade ?? 'Brasileira',
    sexo: normalizeSexo(props.profile.sexo),
    endereco: props.profile.endereco ?? '',
    endereco_numero: props.profile.endereco_numero ?? '',
    bairro: props.profile.bairro ?? '',
    cep: cepDigitsOnly(String(props.profile.cep ?? '')),
    cidade: props.profile.cidade ?? '',
    endereco_uf: props.profile.endereco_uf ?? '',
    pais: props.profile.pais ?? 'Brasil',
    telefone: props.profile.telefone ?? '',
    telefone_fixo: props.profile.telefone_fixo ?? '',
    foto: null as File | null,
});

const cepLookupLoading = ref(false);
const cepLookupMessage = ref<string | null>(null);
const fotoPreviewUrl = ref<string | null>(null);
const fotoLoadError = ref(false);
const existingFotoUrl = computed(() => props.profile.foto_url ?? null);

const completion = computed(() => getProfileCompletion({
    ...props.profile,
    ...form.data(),
    cpf: props.profile.cpf,
    foto_path: form.foto ? 'pending' : props.profile.foto_url,
}));

const cpfDisplay = computed(() => formatCpfDisplay(props.profile.cpf ?? ''));
const isEmailVerified = computed(() => Boolean(props.profile.email_verified_at));

const sections = [
    { id: 'foto', label: 'Foto', fields: [] as string[] },
    { id: 'pessoais', label: 'Dados pessoais', fields: ['name', 'data_nascimento', 'naturalidade', 'nacionalidade', 'sexo'] },
    { id: 'documento', label: 'Documento', fields: ['identidade', 'orgao_emissor', 'identidade_uf', 'identidade_data_emissao'] },
    { id: 'endereco', label: 'Endereço', fields: ['endereco', 'endereco_numero', 'bairro', 'cep', 'cidade', 'endereco_uf', 'pais'] },
    { id: 'contato', label: 'Contato', fields: ['telefone', 'email'] },
] as const;

type SectionId = typeof sections[number]['id'];

function isSectionFilled(sectionId: SectionId): boolean {
    const section = sections.find((s) => s.id === sectionId);
    if (!section) {
        return false;
    }

    if (sectionId === 'foto') {
        return Boolean(form.foto ?? props.profile.foto_url);
    }

    const merged = { ...props.profile, ...form.data(), cpf: props.profile.cpf };

    return section.fields.every((field) => {
        const val = (merged as Record<string, unknown>)[field];
        return val !== null && val !== undefined && String(val).trim() !== '';
    });
}

function revokeFotoPreview(): void {
    if (fotoPreviewUrl.value !== null) {
        URL.revokeObjectURL(fotoPreviewUrl.value);
        fotoPreviewUrl.value = null;
    }
}

onUnmounted(revokeFotoPreview);

function onFotoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    revokeFotoPreview();
    form.foto = file !== null ? normalizeUploadFile(file, 'foto') : null;
    form.clearErrors('foto');
    fotoLoadError.value = false;
    if (file !== null) {
        fotoPreviewUrl.value = URL.createObjectURL(file);
    }
}

function onCepInput(value: string | number): void {
    form.cep = cepDigitsOnly(String(value));
    form.clearErrors('cep');
    cepLookupMessage.value = null;
    debouncedCepLookup();
}

type ViaCepResponse = {
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
        await nextTick();
        document.getElementById(fieldId('endereco_numero'))?.focus();
    } catch {
        cepLookupMessage.value = 'Não foi possível consultar o CEP. Tente novamente.';
    } finally {
        cepLookupLoading.value = false;
    }
}

const debouncedCepLookup = useDebounceFn(lookupCep, 500);

function submit(): void {
    const url = props.embedded
        ? `${ProfileController.update.url()}?stay_on_page=1&enrollment_step=${props.enrollmentStep}`
        : ProfileController.update.url();

    const hasNewPhoto = form.foto instanceof File;

    // Multipart só quando há arquivo: com PATCH + FormData sem foto o PHP/Laravel
    // frequentemente não recebe os campos e retorna "obrigatório" em tudo.
    form.patch(url, {
        forceFormData: hasNewPhoto,
        preserveScroll: true,
        preserveState: props.embedded,
        onSuccess: () => {
            emit('saved');
        },
    });
}

function cancelEdit(): void {
    emit('cancel');
}

const activeFotoSrc = computed(() => fotoPreviewUrl.value ?? existingFotoUrl.value);
const displayName = computed(() => props.profile.name ?? 'Candidato');
const avatarInitials = computed(() => getInitials(displayName.value));
</script>

<template>
    <div class="space-y-6">
        <CandidateProfileCompletion
            v-if="!embedded"
            :filled="completion.filled"
            :total="completion.total"
            :percent="completion.percent"
            :missing="completion.missing"
            :is-complete="completion.isComplete"
        />

        <nav
            v-if="!embedded"
            class="sticky top-0 z-10 -mx-1 flex gap-1 overflow-x-auto rounded-xl border bg-background/95 px-1 py-1.5 backdrop-blur supports-[backdrop-filter]:bg-background/80"
            aria-label="Seções do perfil"
        >
            <a
                v-for="section in sections"
                :key="section.id"
                :href="`#perfil-${section.id}`"
                class="group flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            >
                <CheckCircle2
                    v-if="isSectionFilled(section.id)"
                    :size="11"
                    class="shrink-0 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
                <span
                    :class="isSectionFilled(section.id) ? 'text-foreground' : ''"
                >
                    {{ section.label }}
                </span>
            </a>
        </nav>

        <LgpdDataProtectionNotice variant="compact" class="mb-2" />

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <Card
                :id="embedded ? undefined : 'perfil-foto'"
                class="overflow-hidden border-border/80 shadow-sm"
                :class="embedded ? '' : 'scroll-mt-24'"
            >
                <CardHeader class="space-y-1 border-b border-border/60 bg-muted/30 pb-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Camera :size="18" />
                        </div>
                        <div>
                            <CardTitle class="text-base">Foto de perfil</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Usada na inscrição e na identificação nas etapas do processo.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4 pt-5">
                    <!-- Current photo preview -->
                    <div class="flex items-center gap-4 rounded-xl border border-border/60 bg-muted/20 p-4">
                        <Avatar class="size-24 shrink-0 overflow-hidden rounded-xl ring-2 ring-border">
                            <AvatarImage
                                v-if="activeFotoSrc && !fotoLoadError"
                                :src="activeFotoSrc"
                                alt="Foto de perfil"
                                class="object-cover"
                                @error="fotoLoadError = true"
                            />
                            <AvatarFallback
                                class="rounded-xl bg-gradient-to-br from-primary/20 to-primary/5 text-xl font-bold text-primary"
                            >
                                {{ avatarInitials }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-foreground">
                                {{ activeFotoSrc && !fotoLoadError ? 'Foto atual' : 'Nenhuma foto carregada' }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Envie uma imagem JPG, PNG ou WebP com até 5&nbsp;MB.
                                A foto é usada na sua inscrição.
                            </p>
                            <label
                                :for="fieldId('foto')"
                                class="mt-2.5 inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground shadow-sm transition-colors hover:border-primary/50 hover:bg-primary/5"
                            >
                                <UploadCloud :size="13" aria-hidden="true" />
                                {{ activeFotoSrc && !fotoLoadError ? 'Trocar foto' : 'Selecionar foto' }}
                            </label>
                        </div>
                    </div>

                    <input
                        :id="fieldId('foto')"
                        type="file"
                        name="foto"
                        accept="image/jpeg,image/png,image/webp"
                        class="sr-only"
                        @change="onFotoChange"
                    />
                    <InputError :message="form.errors.foto" />
                </CardContent>
            </Card>

            <Card
                :id="embedded ? undefined : 'perfil-pessoais'"
                class="overflow-hidden border-border/80 shadow-sm"
                :class="embedded ? '' : 'scroll-mt-24'"
            >
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
                                Nome e informações civis conforme documento oficial.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5 pt-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label :for="fieldId('name')">Nome completo *</Label>
                            <Input :id="fieldId('name')" v-model="form.name" name="name" autocomplete="name" />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('data_nascimento')">Data de nascimento *</Label>
                            <Input
                                :id="fieldId('data_nascimento')"
                                v-model="form.data_nascimento"
                                type="date"
                                name="data_nascimento"
                            />
                            <InputError :message="form.errors.data_nascimento" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('cpf-display')">CPF</Label>
                            <div class="relative">
                                <Input
                                    :id="fieldId('cpf-display')"
                                    :model-value="cpfDisplay"
                                    type="text"
                                    disabled
                                    class="pr-10 font-mono tabular-nums opacity-80"
                                />
                                <Lock
                                    class="pointer-events-none absolute right-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground">
                                O CPF não pode ser alterado após o cadastro.
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('naturalidade')">Naturalidade *</Label>
                            <Input :id="fieldId('naturalidade')" v-model="form.naturalidade" name="naturalidade" />
                            <InputError :message="form.errors.naturalidade" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('nacionalidade')">Nacionalidade *</Label>
                            <Input :id="fieldId('nacionalidade')" v-model="form.nacionalidade" name="nacionalidade" />
                            <InputError :message="form.errors.nacionalidade" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label :for="fieldId('sexo')">Sexo *</Label>
                            <select :id="fieldId('sexo')" v-model="form.sexo" name="sexo" :class="selectClass">
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

            <Card
                :id="embedded ? undefined : 'perfil-documento'"
                class="overflow-hidden border-border/80 shadow-sm"
                :class="embedded ? '' : 'scroll-mt-24'"
            >
                <CardHeader class="space-y-1 border-b border-border/60 bg-muted/30 pb-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Lock :size="18" />
                        </div>
                        <div>
                            <CardTitle class="text-base">Documento de identificação</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Dados do RG ou documento equivalente.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="pt-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="fieldId('identidade')">Identidade (RG) *</Label>
                            <Input :id="fieldId('identidade')" v-model="form.identidade" name="identidade" />
                            <InputError :message="form.errors.identidade" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('orgao_emissor')">Órgão emissor *</Label>
                            <Input :id="fieldId('orgao_emissor')" v-model="form.orgao_emissor" name="orgao_emissor" />
                            <InputError :message="form.errors.orgao_emissor" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('identidade_uf')">UF (identidade) *</Label>
                            <select
                                :id="fieldId('identidade_uf')"
                                v-model="form.identidade_uf"
                                name="identidade_uf"
                                :class="selectClass"
                            >
                                <option disabled value="">Selecione</option>
                                <option v-for="uf in ufs" :key="`id-${uf}`" :value="uf">
                                    {{ uf }}
                                </option>
                            </select>
                            <InputError :message="form.errors.identidade_uf" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('identidade_data_emissao')">Data de emissão *</Label>
                            <Input
                                :id="fieldId('identidade_data_emissao')"
                                v-model="form.identidade_data_emissao"
                                type="date"
                                name="identidade_data_emissao"
                            />
                            <InputError :message="form.errors.identidade_data_emissao" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card
                :id="embedded ? undefined : 'perfil-endereco'"
                class="overflow-hidden border-border/80 shadow-sm"
                :class="embedded ? '' : 'scroll-mt-24'"
            >
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
                                Informe o CEP para preencher logradouro, bairro, cidade e UF.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5 pt-5">
                    <div
                        class="rounded-xl border border-dashed border-primary/25 bg-primary/[0.03] p-4"
                    >
                        <div class="grid gap-2">
                            <Label :for="fieldId('cep')">CEP *</Label>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <div class="relative min-w-0 flex-1">
                                    <Input
                                        :id="fieldId('cep')"
                                        :model-value="formatCepDisplay(form.cep)"
                                        inputmode="numeric"
                                        maxlength="9"
                                        placeholder="00000-000"
                                        class="pr-10 font-mono tabular-nums"
                                        @update:model-value="onCepInput"
                                    />
                                    <Loader2
                                        v-if="cepLookupLoading"
                                        class="absolute right-2.5 top-1/2 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    class="shrink-0"
                                    :disabled="cepDigitsOnly(form.cep).length !== 8 || cepLookupLoading"
                                    @click="lookupCep"
                                >
                                    Buscar CEP
                                </Button>
                            </div>
                            <p v-if="cepLookupMessage" class="text-xs font-medium text-primary">
                                {{ cepLookupMessage }}
                            </p>
                            <InputError :message="form.errors.cep" />
                        </div>
                    </div>

                    <Separator />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label :for="fieldId('endereco')">Logradouro *</Label>
                            <Input :id="fieldId('endereco')" v-model="form.endereco" name="endereco" />
                            <InputError :message="form.errors.endereco" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('endereco_numero')">Número *</Label>
                            <Input :id="fieldId('endereco_numero')" v-model="form.endereco_numero" name="endereco_numero" />
                            <InputError :message="form.errors.endereco_numero" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('bairro')">Bairro *</Label>
                            <Input :id="fieldId('bairro')" v-model="form.bairro" name="bairro" />
                            <InputError :message="form.errors.bairro" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('cidade')">Cidade *</Label>
                            <Input :id="fieldId('cidade')" v-model="form.cidade" name="cidade" />
                            <InputError :message="form.errors.cidade" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('endereco_uf')">UF *</Label>
                            <select :id="fieldId('endereco_uf')" v-model="form.endereco_uf" name="endereco_uf" :class="selectClass">
                                <option disabled value="">Selecione</option>
                                <option v-for="uf in ufs" :key="`ed-${uf}`" :value="uf">
                                    {{ uf }}
                                </option>
                            </select>
                            <InputError :message="form.errors.endereco_uf" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label :for="fieldId('pais')">País *</Label>
                            <Input :id="fieldId('pais')" v-model="form.pais" name="pais" />
                            <InputError :message="form.errors.pais" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card
                :id="embedded ? undefined : 'perfil-contato'"
                class="overflow-hidden border-border/80 shadow-sm"
                :class="embedded ? '' : 'scroll-mt-24'"
            >
                <CardHeader class="space-y-1 border-b border-border/60 bg-muted/30 pb-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Phone :size="18" />
                        </div>
                        <div>
                            <CardTitle class="text-base">Contato</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Telefones e e-mail de acesso à conta.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5 pt-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="fieldId('telefone')">Telefone celular *</Label>
                            <Input :id="fieldId('telefone')" v-model="form.telefone" name="telefone" type="tel" />
                            <InputError :message="form.errors.telefone" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="fieldId('telefone_fixo')">Telefone fixo</Label>
                            <Input :id="fieldId('telefone_fixo')" v-model="form.telefone_fixo" name="telefone_fixo" type="tel" />
                            <InputError :message="form.errors.telefone_fixo" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label :for="fieldId('email')">
                                <span class="inline-flex items-center gap-1.5">
                                    <Mail :size="14" class="text-muted-foreground" />
                                    E-mail *
                                </span>
                            </Label>
                            <Input :id="fieldId('email')" v-model="form.email" name="email" type="email" autocomplete="username" />
                            <InputError :message="form.errors.email" />
                            <p
                                v-if="isEmailVerified"
                                class="text-xs text-emerald-600 dark:text-emerald-400"
                            >
                                E-mail verificado.
                            </p>
                            <div v-else-if="mustVerifyEmail" class="space-y-2">
                                <p class="text-xs text-muted-foreground">
                                    Seu e-mail ainda não foi verificado.
                                    <Link
                                        :href="send()"
                                        as="button"
                                        class="font-medium text-foreground underline underline-offset-2"
                                    >
                                        Reenviar link de verificação
                                    </Link>
                                </p>
                                <div
                                    v-if="status === 'verification-link-sent'"
                                    class="rounded-md bg-emerald-100 px-3 py-2 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                                >
                                    Um novo link foi enviado para o seu e-mail.
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div
                class="flex flex-col gap-3 rounded-xl border bg-background/95 p-4 backdrop-blur sm:flex-row sm:items-center sm:justify-between supports-[backdrop-filter]:bg-background/80"
                :class="embedded ? '' : 'sticky bottom-0 z-10'"
            >
                <p class="text-sm text-muted-foreground">
                    {{
                        embedded
                            ? 'Salve para atualizar os dados desta inscrição. Você também pode cancelar e seguir para a próxima etapa.'
                            : 'Revise os dados antes de salvar. Alterações no e-mail exigem nova verificação.'
                    }}
                </p>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    <Button
                        v-if="embedded"
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="cancelEdit"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing" data-test="update-profile-button">
                        {{ form.processing ? 'Salvando…' : 'Salvar alterações' }}
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
