<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Shield } from 'lucide-vue-next';
import { computed } from 'vue';
import LgpdPrivacyPolicyContent from '@/components/Lgpd/LgpdPrivacyPolicyContent.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const open = defineModel<boolean>('open', { default: false });

const page = usePage();

const dataController = computed(
    () =>
        (page.props.lgpd as { data_controller?: string } | undefined)
            ?.data_controller ?? 'esta instituição',
);

const contactEmail = computed(
    () =>
        (page.props.lgpd as { contact_email?: string } | undefined)
            ?.contact_email ?? '',
);

function close(): void {
    open.value = false;
}

defineExpose({
    open: () => {
        open.value = true;
    },
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="flex max-h-[min(90dvh,40rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-2xl"
            @escape-key-down="close"
        >
            <DialogHeader
                class="shrink-0 border-b border-border px-6 py-4 text-left"
            >
                <DialogTitle class="flex items-center gap-2 pr-8 text-left">
                    <Shield
                        class="size-5 shrink-0 text-amber-700 dark:text-amber-400"
                        aria-hidden="true"
                    />
                    Política de Privacidade e Proteção de Dados
                </DialogTitle>
                <DialogDescription class="text-left">
                    Em conformidade com a Lei nº 13.709/2018 (LGPD)
                </DialogDescription>
            </DialogHeader>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                <LgpdPrivacyPolicyContent
                    :data-controller="dataController"
                    :contact-email="contactEmail"
                />
            </div>

            <DialogFooter class="shrink-0 border-t border-border px-6 py-4">
                <Button type="button" class="w-full sm:w-auto" @click="close">
                    Fechar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
