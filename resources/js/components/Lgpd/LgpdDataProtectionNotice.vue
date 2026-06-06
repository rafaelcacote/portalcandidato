<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Shield } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import LgpdPrivacyPolicyDialog from '@/components/Lgpd/LgpdPrivacyPolicyDialog.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Variant = 'compact' | 'detailed';

const props = withDefaults(
    defineProps<{
        variant?: Variant;
        /** Exibe link para a política de privacidade completa. */
        showPolicyLink?: boolean;
        /**
         * Quando true, emite `openPolicy` em vez de abrir o dialog interno
         * (útil para compartilhar um único dialog na página pai).
         */
        externalPolicyDialog?: boolean;
        class?: string;
    }>(),
    {
        variant: 'detailed',
        showPolicyLink: true,
        externalPolicyDialog: false,
    },
);

const emit = defineEmits<{
    openPolicy: [];
}>();

const page = usePage();
const policyDialogOpen = ref(false);

const dataController = computed(
    () =>
        (page.props.lgpd as { data_controller?: string } | undefined)
            ?.data_controller ?? 'esta instituição',
);

function openPrivacyPolicy(): void {
    if (props.externalPolicyDialog) {
        emit('openPolicy');
    } else {
        policyDialogOpen.value = true;
    }
}
</script>

<template>
    <Alert
        role="note"
        :class="[
            'border-amber-200/90 bg-amber-50/90 text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100',
            props.class,
        ]"
    >
        <Shield class="text-amber-700 dark:text-amber-400" aria-hidden="true" />
        <AlertTitle class="text-amber-950 dark:text-amber-50">
            Proteção de dados pessoais (LGPD)
        </AlertTitle>
        <AlertDescription class="text-amber-900/90 dark:text-amber-100/90">
            <template v-if="variant === 'compact'">
                <p>
                    Tratamos dados pessoais e sensíveis (CPF, documento, foto,
                    endereço e contato) conforme a
                    <strong>Lei nº 13.709/2018 (LGPD)</strong>, exclusivamente
                    para o processo seletivo de {{ dataController }}.
                </p>
            </template>
            <template v-else>
                <p>
                    Este portal coleta e trata
                    <strong>dados pessoais e sensíveis</strong>, incluindo CPF,
                    documento de identidade, foto de perfil, endereço, telefone
                    e e-mail, em conformidade com a
                    <strong
                        >Lei Geral de Proteção de Dados (LGPD — Lei nº
                        13.709/2018)</strong
                    >.
                </p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>
                        <strong>Finalidade:</strong> cadastro, identificação e
                        participação em processos seletivos de
                        {{ dataController }}.
                    </li>
                    <li>
                        <strong>Segurança:</strong> armazenamento com controle
                        de acesso, criptografia em trânsito (HTTPS) e medidas
                        técnicas para proteger seus dados.
                    </li>
                    <li>
                        <strong>Seus direitos:</strong> acesso, correção,
                        portabilidade, anonimização, eliminação e revogação do
                        consentimento, nos termos da LGPD.
                    </li>
                </ul>
            </template>
            <p v-if="showPolicyLink" class="mt-2">
                <button
                    type="button"
                    class="font-semibold text-amber-900 underline decoration-amber-700/40 underline-offset-2 hover:decoration-amber-800 dark:text-amber-50 dark:decoration-amber-300/50"
                    @click="openPrivacyPolicy"
                >
                    Leia a Política de Privacidade completa
                </button>
            </p>
        </AlertDescription>
    </Alert>

    <LgpdPrivacyPolicyDialog
        v-if="!externalPolicyDialog"
        v-model:open="policyDialogOpen"
    />
</template>
