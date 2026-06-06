<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    BookUser,
    Camera,
    CheckCircle2,
    FileText,
    Loader2,
    Mail,
    MapPin,
    UploadCloud,
} from 'lucide-vue-next';
import Button from 'primevue/button';
import type { Component, HTMLAttributes } from 'vue';
import { computed, nextTick, onUnmounted, reactive, ref, watch } from 'vue';
import CandidateHeader from '@/components/Candidate/CandidateHeader.vue';
import InputError from '@/components/InputError.vue';
import LgpdDataProtectionNotice from '@/components/Lgpd/LgpdDataProtectionNotice.vue';
import LgpdPrivacyPolicyDialog from '@/components/Lgpd/LgpdPrivacyPolicyDialog.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
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
    cepDigitsOnly,
    cpfDigitsOnly,
    formatCepDisplay,
    isValidCpfDigits,
} from '@/lib/brDocuments';
import { normalizeUploadFile } from '@/lib/uploadFile';
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
    'h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-base text-foreground [color-scheme:light] shadow-xs outline-none md:text-sm dark:[color-scheme:dark]',
    'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
    'disabled:cursor-not-allowed disabled:opacity-50',
);

const OPTIONAL_FIELDS = new Set(['telefone_fixo']);

// ── Step management ─────────────────────────────────────────────────────────

type StepId = 1 | 2 | 3 | 4 | 5;

type StepDef = {
    id: StepId;
    label: string;
    icon: Component;
    description: string;
};

const steps: StepDef[] = [
    {
        id: 1,
        label: 'Foto',
        icon: Camera,
        description: 'Sua identificação visual',
    },
    {
        id: 2,
        label: 'Dados pessoais',
        icon: BookUser,
        description: 'Informações civis',
    },
    {
        id: 3,
        label: 'Documento',
        icon: FileText,
        description: 'RG e órgão emissor',
    },
    { id: 4, label: 'Endereço', icon: MapPin, description: 'Residência atual' },
    { id: 5, label: 'Acesso', icon: Mail, description: 'Contato e senha' },
];

const activeStep = ref<StepId>(1);
const completedSteps = reactive(new Set<StepId>());

const stepFieldsMap: Record<StepId, string[]> = {
    1: ['foto'],
    2: [
        'name',
        'data_nascimento',
        'cpf',
        'naturalidade',
        'nacionalidade',
        'sexo',
    ],
    3: [
        'identidade',
        'orgao_emissor',
        'identidade_uf',
        'identidade_data_emissao',
    ],
    4: [
        'cep',
        'endereco',
        'endereco_numero',
        'bairro',
        'cidade',
        'endereco_uf',
        'pais',
    ],
    5: [
        'telefone',
        'email',
        'email_confirmation',
        'password',
        'password_confirmation',
    ],
};

const formFocusRef = ref<HTMLElement | null>(null);

async function scrollToForm(): Promise<void> {
    await nextTick();
    formFocusRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function touchStepFields(stepId: StepId): void {
    for (const field of stepFieldsMap[stepId]) {
        touch(field);
    }
}

function isStepFieldsValid(stepId: StepId): boolean {
    return stepFieldsMap[stepId].every((f) => !fieldInvalid(f));
}

function canNavigateToStep(stepId: StepId): boolean {
    return stepId <= activeStep.value || completedSteps.has(stepId);
}

function goToStep(stepId: StepId): void {
    if (canNavigateToStep(stepId)) {
        activeStep.value = stepId;
        void scrollToForm();
    }
}

function nextStep(): void {
    const current = activeStep.value;
    touchStepFields(current);

    if (
        current === 2 &&
        (cpfCheckStatus.value === 'taken' || cpfCheckStatus.value === 'invalid')
    ) {
        return;
    }

    if (!isStepFieldsValid(current)) {
        return;
    }

    completedSteps.add(current);

    if (current < 5) {
        activeStep.value = (current + 1) as StepId;
        void scrollToForm();
    }
}

function prevStep(): void {
    if (activeStep.value > 1) {
        activeStep.value = (activeStep.value - 1) as StepId;
        void scrollToForm();
    }
}

function stepCircleClass(stepId: StepId): string {
    const base =
        'flex size-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold transition-all duration-200';

    if (completedSteps.has(stepId)) {
        return `${base} bg-emerald-50 border border-emerald-200/80 text-emerald-700 dark:bg-emerald-950/30 dark:border-emerald-800/60 dark:text-emerald-400`;
    }

    if (activeStep.value === stepId) {
        return `${base} bg-primary text-primary-foreground shadow-sm ring-4 ring-primary/15 scale-105`;
    }

    return `${base} bg-muted/40 border border-border/60 text-muted-foreground`;
}

const progressPercent = computed(() => ((activeStep.value - 1) / 4) * 100);
const currentStepDef = computed(
    () => steps.find((s) => s.id === activeStep.value)!,
);

// ── Form data ────────────────────────────────────────────────────────────────

const form = useForm({
    name: '',
    email: '',
    email_confirmation: '',
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

const cpfCheckStatus = ref<
    'idle' | 'loading' | 'available' | 'taken' | 'invalid'
>('idle');
const cepLookupLoading = ref(false);
const cepLookupMessage = ref<string | null>(null);
const fotoPreviewUrl = ref<string | null>(null);
const privacyPolicyDialogOpen = ref(false);

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
        if (
            cpfCheckStatus.value === 'taken' ||
            cpfCheckStatus.value === 'invalid'
        ) {
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
    form.foto = file !== null ? normalizeUploadFile(file, 'foto') : null;
    form.clearErrors('foto');

    if (file !== null) {
        fotoPreviewUrl.value = URL.createObjectURL(form.foto!);
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
        const res = await fetch(
            `${CHECK_CPF_URL}?cpf=${encodeURIComponent(digits)}`,
            {
                headers: { Accept: 'application/json' },
            },
        );
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
            cepLookupMessage.value =
                'CEP não encontrado. Verifique os dígitos.';
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
        cepLookupMessage.value =
            'Não foi possível consultar o CEP. Tente novamente.';
        await focusAndSelectCepField();
    } finally {
        cepLookupLoading.value = false;
    }
}

const debouncedCepLookup = useDebounceFn(lookupCep, 500);

function submit(): void {
    submitAttempted.value = true;

    if (
        cpfCheckStatus.value === 'taken' ||
        cpfCheckStatus.value === 'invalid'
    ) {
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

        <LgpdDataProtectionNotice
            class="mb-5 sm:mb-6"
            external-policy-dialog
            @open-policy="privacyPolicyDialogOpen = true"
        />

        <LgpdPrivacyPolicyDialog v-model:open="privacyPolicyDialogOpen" />

        <!-- ── Indicador de etapas ──────────────────────────────────────────── -->
        <div
            ref="formFocusRef"
            class="mb-6 scroll-mt-4 overflow-hidden rounded-2xl border border-border/60 bg-card shadow-sm md:scroll-mt-6"
        >
            <div class="flex">
                <button
                    v-for="step in steps"
                    :key="step.id"
                    type="button"
                    class="group flex flex-1 flex-col items-center gap-1.5 border-r border-border/40 px-2 py-3 transition-colors last:border-r-0 sm:gap-2 sm:py-4"
                    :class="[
                        canNavigateToStep(step.id)
                            ? 'cursor-pointer hover:bg-muted/30'
                            : 'cursor-default',
                        activeStep === step.id ? 'bg-primary/[0.03]' : '',
                    ]"
                    :disabled="!canNavigateToStep(step.id)"
                    :aria-current="activeStep === step.id ? 'step' : undefined"
                    @click="canNavigateToStep(step.id) && goToStep(step.id)"
                >
                    <div :class="stepCircleClass(step.id)">
                        <CheckCircle2
                            v-if="completedSteps.has(step.id)"
                            :size="18"
                            aria-hidden="true"
                        />
                        <component
                            :is="step.icon"
                            v-else-if="activeStep !== step.id"
                            :size="16"
                            class="opacity-70"
                            aria-hidden="true"
                        />
                        <span v-else>{{ step.id }}</span>
                    </div>
                    <span
                        class="hidden text-[10px] leading-none font-semibold sm:block"
                        :class="{
                            'text-foreground': activeStep === step.id,
                            'text-emerald-700 dark:text-emerald-400':
                                completedSteps.has(step.id) &&
                                activeStep !== step.id,
                            'text-muted-foreground':
                                activeStep !== step.id &&
                                !completedSteps.has(step.id),
                        }"
                    >
                        {{ step.label }}
                    </span>
                </button>
            </div>

            <!-- Barra de progresso -->
            <div class="h-1 bg-muted/40">
                <div
                    class="h-full bg-primary transition-all duration-500 ease-in-out"
                    :style="{ width: `${progressPercent}%` }"
                    role="progressbar"
                    :aria-valuenow="activeStep"
                    aria-valuemin="1"
                    aria-valuemax="5"
                />
            </div>

            <!-- Info da etapa atual -->
            <div class="flex items-center justify-between gap-2 px-4 py-2">
                <div class="flex items-center gap-2">
                    <component
                        :is="currentStepDef.icon"
                        :size="14"
                        class="shrink-0 text-primary"
                        aria-hidden="true"
                    />
                    <span class="text-xs font-semibold text-foreground">
                        {{ currentStepDef.label }}
                    </span>
                    <span
                        class="hidden text-xs text-muted-foreground sm:inline"
                    >
                        · {{ currentStepDef.description }}
                    </span>
                </div>
                <span class="text-xs text-muted-foreground tabular-nums">
                    {{ activeStep }}&nbsp;/&nbsp;5
                </span>
            </div>
        </div>

        <!-- ── Formulário ──────────────────────────────────────────────────── -->
        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <!-- Etapa 1 · Foto de perfil ─────────────────────────────────── -->
            <template v-if="activeStep === 1">
                <Card class="overflow-hidden border-border/80 shadow-sm">
                    <CardHeader
                        class="border-b border-border/60 bg-muted/30 pb-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <Camera :size="20" />
                            </div>
                            <div>
                                <CardTitle class="text-base"
                                    >Foto de perfil</CardTitle
                                >
                                <CardDescription class="text-xs sm:text-sm">
                                    Sua foto é obrigatória e será usada para
                                    identificação na inscrição.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-5">
                        <div class="grid gap-4">
                            <!-- Pré-visualização quando uma foto já foi selecionada -->
                            <div
                                v-if="fotoPreviewUrl"
                                class="flex items-center gap-4 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20"
                            >
                                <img
                                    :src="fotoPreviewUrl"
                                    alt="Pré-visualização da foto"
                                    class="size-24 shrink-0 rounded-xl border border-border/60 object-cover shadow-sm"
                                />
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="flex items-center gap-1.5 text-sm font-semibold text-emerald-800 dark:text-emerald-300"
                                    >
                                        <CheckCircle2
                                            :size="15"
                                            aria-hidden="true"
                                        />
                                        Foto selecionada com sucesso
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        Verifique a imagem antes de continuar.
                                        Ela será usada na sua ficha de
                                        inscrição.
                                    </p>
                                    <label
                                        for="foto"
                                        class="mt-2.5 inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 text-xs font-medium transition-colors hover:bg-muted"
                                    >
                                        <UploadCloud
                                            :size="13"
                                            aria-hidden="true"
                                        />
                                        Trocar foto
                                    </label>
                                </div>
                            </div>

                            <!-- Área de upload quando nenhuma foto foi selecionada -->
                            <label
                                v-else
                                for="foto"
                                class="flex cursor-pointer flex-col items-center justify-center gap-4 rounded-2xl border-2 border-dashed p-10 text-center transition-colors"
                                :class="
                                    fieldInvalid('foto')
                                        ? 'border-destructive bg-destructive/[0.03]'
                                        : 'border-border/60 hover:border-primary/50 hover:bg-primary/[0.02]'
                                "
                            >
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                                >
                                    <Camera :size="30" aria-hidden="true" />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        Clique para selecionar sua foto
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        JPG, PNG ou WebP · máximo 5&nbsp;MB
                                    </p>
                                </div>
                                <div
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-sm"
                                >
                                    <UploadCloud
                                        :size="13"
                                        aria-hidden="true"
                                    />
                                    Selecionar foto
                                </div>
                            </label>

                            <input
                                id="foto"
                                type="file"
                                name="foto"
                                accept="image/jpeg,image/png,image/webp"
                                class="sr-only"
                                :aria-invalid="fieldInvalid('foto')"
                                @change="onFotoChange"
                                @blur="touch('foto')"
                            />
                            <InputError :message="form.errors.foto" />
                            <p
                                v-if="fieldInvalid('foto') && !form.errors.foto"
                                class="text-xs text-destructive"
                            >
                                A foto de perfil é obrigatória para o cadastro.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </template>

            <!-- Etapa 2 · Dados pessoais ─────────────────────────────────── -->
            <template v-else-if="activeStep === 2">
                <Card class="overflow-hidden border-border/80 shadow-sm">
                    <CardHeader
                        class="border-b border-border/60 bg-muted/30 pb-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <BookUser :size="20" />
                            </div>
                            <div>
                                <CardTitle class="text-base"
                                    >Dados pessoais</CardTitle
                                >
                                <CardDescription class="text-xs sm:text-sm">
                                    Identidade e informações civis conforme
                                    documento oficial.
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
                                <Label for="data_nascimento"
                                    >Data de nascimento *</Label
                                >
                                <Input
                                    id="data_nascimento"
                                    v-model="form.data_nascimento"
                                    type="date"
                                    name="data_nascimento"
                                    :class="
                                        inputInvalidClass('data_nascimento')
                                    "
                                    :aria-invalid="
                                        fieldInvalid('data_nascimento')
                                    "
                                    @blur="touch('data_nascimento')"
                                />
                                <InputError
                                    :message="form.errors.data_nascimento"
                                />
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
                                        :class="
                                            cn(
                                                'pr-10 tabular-nums',
                                                inputInvalidClass('cpf'),
                                            )
                                        "
                                        :aria-invalid="fieldInvalid('cpf')"
                                        @update:model-value="onCpfInput"
                                        @blur="touch('cpf')"
                                    />
                                    <div
                                        class="pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-foreground"
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
                                    :class="{
                                        'text-green-600 dark:text-green-400':
                                            cpfCheckStatus === 'available',
                                        'text-destructive':
                                            cpfCheckStatus === 'taken' ||
                                            cpfCheckStatus === 'invalid',
                                        'text-muted-foreground':
                                            cpfCheckStatus === 'loading',
                                    }"
                                >
                                    {{ cpfHint }}
                                </p>
                                <InputError :message="form.errors.cpf" />
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
                                <InputError
                                    :message="form.errors.naturalidade"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="nacionalidade"
                                    >Nacionalidade *</Label
                                >
                                <Input
                                    id="nacionalidade"
                                    v-model="form.nacionalidade"
                                    type="text"
                                    name="nacionalidade"
                                    :class="inputInvalidClass('nacionalidade')"
                                    :aria-invalid="
                                        fieldInvalid('nacionalidade')
                                    "
                                    @blur="touch('nacionalidade')"
                                />
                                <InputError
                                    :message="form.errors.nacionalidade"
                                />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="sexo">Sexo *</Label>
                                <select
                                    id="sexo"
                                    v-model="form.sexo"
                                    name="sexo"
                                    :class="
                                        cn(
                                            selectClass,
                                            selectInvalidClass('sexo'),
                                        )
                                    "
                                    :aria-invalid="fieldInvalid('sexo')"
                                    @blur="touch('sexo')"
                                >
                                    <option disabled value="">Selecione</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="feminino">Feminino</option>
                                    <option value="outro">Outro</option>
                                    <option value="prefiro_nao_informar">
                                        Prefiro não informar
                                    </option>
                                </select>
                                <InputError :message="form.errors.sexo" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>

            <!-- Etapa 3 · Documento de identificação ─────────────────────── -->
            <template v-else-if="activeStep === 3">
                <Card class="overflow-hidden border-border/80 shadow-sm">
                    <CardHeader
                        class="border-b border-border/60 bg-muted/30 pb-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <FileText :size="20" />
                            </div>
                            <div>
                                <CardTitle class="text-base"
                                    >Documento de identificação</CardTitle
                                >
                                <CardDescription class="text-xs sm:text-sm">
                                    Dados do RG ou documento oficial
                                    equivalente.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-5 pt-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="identidade"
                                    >Identidade (RG) *</Label
                                >
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
                                <Label for="orgao_emissor"
                                    >Órgão emissor *</Label
                                >
                                <Input
                                    id="orgao_emissor"
                                    v-model="form.orgao_emissor"
                                    type="text"
                                    name="orgao_emissor"
                                    :class="inputInvalidClass('orgao_emissor')"
                                    :aria-invalid="
                                        fieldInvalid('orgao_emissor')
                                    "
                                    @blur="touch('orgao_emissor')"
                                />
                                <InputError
                                    :message="form.errors.orgao_emissor"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="identidade_uf"
                                    >UF (identidade) *</Label
                                >
                                <select
                                    id="identidade_uf"
                                    v-model="form.identidade_uf"
                                    name="identidade_uf"
                                    :class="
                                        cn(
                                            selectClass,
                                            selectInvalidClass('identidade_uf'),
                                        )
                                    "
                                    :aria-invalid="
                                        fieldInvalid('identidade_uf')
                                    "
                                    @blur="touch('identidade_uf')"
                                >
                                    <option disabled value="">Selecione</option>
                                    <option
                                        v-for="uf in props.ufs"
                                        :key="`id-${uf}`"
                                        :value="uf"
                                    >
                                        {{ uf }}
                                    </option>
                                </select>
                                <InputError
                                    :message="form.errors.identidade_uf"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="identidade_data_emissao"
                                    >Data de emissão (RG) *</Label
                                >
                                <Input
                                    id="identidade_data_emissao"
                                    v-model="form.identidade_data_emissao"
                                    type="date"
                                    name="identidade_data_emissao"
                                    :class="
                                        inputInvalidClass(
                                            'identidade_data_emissao',
                                        )
                                    "
                                    :aria-invalid="
                                        fieldInvalid('identidade_data_emissao')
                                    "
                                    @blur="touch('identidade_data_emissao')"
                                />
                                <InputError
                                    :message="
                                        form.errors.identidade_data_emissao
                                    "
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>

            <!-- Etapa 4 · Endereço residencial ──────────────────────────── -->
            <template v-else-if="activeStep === 4">
                <Card class="overflow-hidden border-border/80 shadow-sm">
                    <CardHeader
                        class="border-b border-border/60 bg-muted/30 pb-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <MapPin :size="20" />
                            </div>
                            <div>
                                <CardTitle class="text-base"
                                    >Endereço residencial</CardTitle
                                >
                                <CardDescription class="text-xs sm:text-sm">
                                    Digite o CEP primeiro — buscamos logradouro,
                                    bairro, cidade e UF automaticamente.
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
                                <div
                                    class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3"
                                >
                                    <div class="relative min-w-0 flex-1">
                                        <Input
                                            id="cep"
                                            :model-value="
                                                formatCepDisplay(form.cep)
                                            "
                                            type="text"
                                            inputmode="numeric"
                                            name="cep"
                                            maxlength="9"
                                            placeholder="00000-000"
                                            autocomplete="postal-code"
                                            :class="
                                                cn(
                                                    'pr-10 font-mono tabular-nums',
                                                    inputInvalidClass('cep'),
                                                )
                                            "
                                            :aria-invalid="fieldInvalid('cep')"
                                            @update:model-value="onCepInput"
                                            @blur="touch('cep')"
                                        />
                                        <div
                                            class="pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-foreground"
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
                                        label="Buscar CEP"
                                        severity="secondary"
                                        outlined
                                        size="small"
                                        class="h-9 w-full shrink-0 sm:w-auto"
                                        :disabled="
                                            cepDigitsOnly(form.cep).length !==
                                                8 || cepLookupLoading
                                        "
                                        @click="lookupCep"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Ao completar 8 dígitos, o endereço é buscado
                                    automaticamente.
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
                                <Label for="endereco"
                                    >Endereço (logradouro) *</Label
                                >
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
                                    :class="
                                        inputInvalidClass('endereco_numero')
                                    "
                                    :aria-invalid="
                                        fieldInvalid('endereco_numero')
                                    "
                                    @blur="touch('endereco_numero')"
                                />
                                <InputError
                                    :message="form.errors.endereco_numero"
                                />
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
                                    :class="
                                        cn(
                                            selectClass,
                                            selectInvalidClass('endereco_uf'),
                                        )
                                    "
                                    :aria-invalid="fieldInvalid('endereco_uf')"
                                    @blur="touch('endereco_uf')"
                                >
                                    <option disabled value="">Selecione</option>
                                    <option
                                        v-for="uf in props.ufs"
                                        :key="`ed-${uf}`"
                                        :value="uf"
                                    >
                                        {{ uf }}
                                    </option>
                                </select>
                                <InputError
                                    :message="form.errors.endereco_uf"
                                />
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
            </template>

            <!-- Etapa 5 · Contato e acesso ──────────────────────────────── -->
            <template v-else-if="activeStep === 5">
                <Card class="overflow-hidden border-border/80 shadow-sm">
                    <CardHeader
                        class="border-b border-border/60 bg-muted/30 pb-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <Mail :size="20" />
                            </div>
                            <div>
                                <CardTitle class="text-base"
                                    >Contato e acesso</CardTitle
                                >
                                <CardDescription class="text-xs sm:text-sm">
                                    Informe seu telefone, e-mail de login e crie
                                    uma senha segura.
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
                                <InputError
                                    :message="form.errors.telefone_fixo"
                                />
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
                            <div class="grid gap-2">
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
                            <div class="grid gap-2">
                                <Label for="email_confirmation"
                                    >Confirmar e-mail *</Label
                                >
                                <Input
                                    id="email_confirmation"
                                    v-model="form.email_confirmation"
                                    type="email"
                                    autocomplete="email"
                                    name="email_confirmation"
                                    :class="
                                        inputInvalidClass('email_confirmation')
                                    "
                                    :aria-invalid="
                                        fieldInvalid('email_confirmation')
                                    "
                                    @blur="touch('email_confirmation')"
                                />
                                <InputError
                                    :message="form.errors.email_confirmation"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="password">Senha *</Label>
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
                            <div class="grid gap-2">
                                <Label for="password_confirmation"
                                    >Confirmar senha *</Label
                                >
                                <PasswordInput
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    autocomplete="new-password"
                                    name="password_confirmation"
                                    :class="
                                        inputInvalidClass(
                                            'password_confirmation',
                                        )
                                    "
                                    :aria-invalid="
                                        fieldInvalid('password_confirmation')
                                    "
                                    @blur="touch('password_confirmation')"
                                />
                                <InputError
                                    :message="form.errors.password_confirmation"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>

            <!-- ── Botões de navegação ──────────────────────────────────────── -->
            <div class="flex items-center justify-between gap-4">
                <Button
                    v-if="activeStep > 1"
                    type="button"
                    label="Voltar"
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    outlined
                    size="small"
                    :disabled="form.processing"
                    @click="prevStep"
                />
                <div class="ml-auto flex items-center gap-3">
                    <Button
                        v-if="activeStep < 5"
                        type="button"
                        label="Próximo"
                        icon="pi pi-arrow-right"
                        icon-pos="right"
                        size="small"
                        :disabled="form.processing"
                        @click="nextStep"
                    />
                    <Button
                        v-else
                        type="submit"
                        label="Criar conta"
                        icon="pi pi-check"
                        icon-pos="right"
                        size="small"
                        :loading="form.processing"
                        data-test="register-user-button"
                    />
                </div>
            </div>

            <div class="space-y-2 text-center text-sm text-muted-foreground">
                <p>
                    Já tem conta?
                    <TextLink
                        :href="login()"
                        class="underline underline-offset-4"
                        >Entrar</TextLink
                    >
                </p>
                <p class="text-xs">
                    <button
                        type="button"
                        class="underline decoration-muted-foreground/40 underline-offset-2 hover:text-foreground"
                        @click="privacyPolicyDialogOpen = true"
                    >
                        Política de Privacidade (LGPD)
                    </button>
                </p>
            </div>
        </form>
    </div>
</template>
