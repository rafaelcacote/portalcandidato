<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { MessageSquare, XCircle } from 'lucide-vue-next';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import { computed, ref, watch } from 'vue';
import CandidateStatusBadge from '@/components/Evaluator/CandidateStatusBadge.vue';
import type { EvaluatorApplicationDocument } from '@/components/Evaluator/evaluatorDocumentTypes';
import evaluatorDocuments from '@/routes/evaluator/candidates/documents';

const props = defineProps<{
    document: EvaluatorApplicationDocument | null;
    applicationId: number;
    visible: boolean;
    /** Quando definido, o dialog salva com este status em vez do status atual do documento. */
    pendingStatus?: 'recusado' | 'aprovado' | null;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    saved: [];
}>();

const isRefusal = computed(() => props.pendingStatus === 'recusado' || props.document?.status === 'recusado');

const localObs = ref('');
const localObsTouched = ref(false);
const obsError = computed(() =>
    isRefusal.value && localObsTouched.value && localObs.value.trim() === ''
        ? 'Informe o motivo da recusa para continuar.'
        : null,
);

watch(
    () => [props.document, props.visible] as const,
    ([doc, vis]) => {
        if (vis) {
            localObs.value = doc?.motivo_recusa ?? '';
            localObsTouched.value = false;
        }
    },
    { immediate: true },
);

const form = useForm({ status: '', motivo_recusa: '' });

function save(): void {
    if (!props.document) {
        return;
    }

    localObsTouched.value = true;

    if (isRefusal.value && localObs.value.trim() === '') {
        return;
    }

    form.status = props.pendingStatus ?? props.document.status;
    form.motivo_recusa = localObs.value;
    form.post(
        evaluatorDocuments.decision({
            application: props.applicationId,
            applicationDocument: props.document.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:visible', false);
                emit('saved');
            },
        },
    );
}

function close(): void {
    emit('update:visible', false);
}

function documentHeading(doc: EvaluatorApplicationDocument): string {
    if (doc.required_document?.nome) {
        return doc.required_document.nome;
    }
    if (doc.title_item?.title) {
        const code = doc.title_item.code ? `${doc.title_item.code} · ` : '';
        return `${code}${doc.title_item.title}`;
    }
    return doc.nome_arquivo;
}
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :dismissable-mask="!isRefusal"
        :closable="true"
        :style="{ width: '34rem', maxWidth: '95vw' }"
        pt:root:class="!rounded-2xl !shadow-2xl !shadow-slate-900/15 !border !border-slate-200/80"
        pt:header:class="!px-6 !pt-5 !pb-0"
        pt:content:class="!px-6 !pb-6"
        @update:visible="emit('update:visible', $event)"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <div
                    :class="[
                        'flex size-9 items-center justify-center rounded-xl',
                        isRefusal ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600',
                    ]"
                >
                    <XCircle v-if="isRefusal" class="size-4.5" />
                    <MessageSquare v-else class="size-4.5" />
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ isRefusal ? 'Recusar documento' : 'Observação' }}
                    </p>
                    <h3 class="mt-0.5 text-sm font-bold leading-tight text-slate-900">
                        {{ document ? documentHeading(document) : '' }}
                    </h3>
                </div>
            </div>
        </template>

        <div v-if="document" class="mt-4 flex flex-col gap-4">
            <!-- Status info -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-slate-500">Status atual:</span>
                <CandidateStatusBadge :status="document.status" size="sm" />
                <template v-if="pendingStatus && pendingStatus !== document.status">
                    <span class="text-xs text-slate-400">→</span>
                    <CandidateStatusBadge :status="pendingStatus" size="sm" />
                </template>
            </div>

            <!-- Aviso de recusa -->
            <div v-if="isRefusal" class="flex items-start gap-2.5 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-200/70">
                <XCircle class="mt-0.5 size-4 shrink-0 text-red-500" />
                <p class="text-xs leading-relaxed text-red-700">
                    Ao recusar este documento, o candidato será notificado e poderá enviar uma nova versão.
                    <strong class="font-semibold">Informe obrigatoriamente o motivo</strong> para que ele possa corrigir.
                </p>
            </div>

            <!-- Textarea -->
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">
                    {{ isRefusal ? 'Motivo da recusa' : 'Observação' }}
                    <span v-if="isRefusal" class="ml-1 font-semibold text-red-500">*</span>
                    <span v-else class="ml-1 font-normal text-slate-400">(opcional)</span>
                </label>
                <Textarea
                    v-model="localObs"
                    rows="5"
                    auto-resize
                    :placeholder="isRefusal
                        ? 'Ex.: documento ilegível, data divergente, falta assinatura, CPF não confere…'
                        : 'Ex.: observação sobre este documento…'"
                    class="w-full"
                    :pt="{
                        root: {
                            class: [
                                'w-full rounded-xl border px-3.5 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 resize-none',
                                obsError
                                    ? 'border-red-300 bg-red-50/40 focus:border-red-400 focus:ring-red-400/20'
                                    : 'border-slate-200 bg-slate-50/60 focus:border-teal-400 focus:ring-teal-400/20',
                            ].join(' '),
                        },
                    }"
                    @blur="localObsTouched = true"
                />
                <p v-if="obsError" class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                    <XCircle class="size-3.5" />
                    {{ obsError }}
                </p>
            </div>

            <!-- Footer buttons -->
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-all hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
                    @click="close"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    :disabled="form.processing"
                    :class="[
                        'inline-flex items-center gap-2 rounded-xl px-5 py-2 text-sm font-semibold text-white shadow-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50',
                        isRefusal
                            ? 'bg-red-500 hover:bg-red-600 focus-visible:ring-red-400/60 shadow-red-500/25'
                            : 'bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 focus-visible:ring-teal-400/60 shadow-teal-600/25',
                    ]"
                    @click="save"
                >
                    <XCircle v-if="isRefusal" class="size-3.5" />
                    <MessageSquare v-else class="size-3.5" />
                    {{ isRefusal ? 'Confirmar recusa' : 'Salvar observação' }}
                </button>
            </div>
        </div>
    </Dialog>
</template>
