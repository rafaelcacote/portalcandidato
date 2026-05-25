<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { KeyRound } from 'lucide-vue-next';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { update as updatePassword } from '@/routes/user-password';

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

watch(open, (isOpen) => {
    if (!isOpen) {
        form.reset();
        form.clearErrors();
    }
});

function submit(): void {
    form.put(updatePassword.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md" @interact-outside="form.processing ? $event.preventDefault() : undefined">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <KeyRound class="size-5 text-primary" aria-hidden="true" />
                    Alterar senha
                </DialogTitle>
                <DialogDescription>
                    Informe sua senha atual e escolha uma nova senha segura para proteger sua conta.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="dialog-current_password">Senha atual</Label>
                    <PasswordInput
                        id="dialog-current_password"
                        v-model="form.current_password"
                        name="current_password"
                        autocomplete="current-password"
                        placeholder="Senha atual"
                    />
                    <InputError :message="form.errors.current_password" />
                </div>

                <div class="grid gap-2">
                    <Label for="dialog-password">Nova senha</Label>
                    <PasswordInput
                        id="dialog-password"
                        v-model="form.password"
                        name="password"
                        autocomplete="new-password"
                        placeholder="Nova senha"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="dialog-password_confirmation">Confirmar nova senha</Label>
                    <PasswordInput
                        id="dialog-password_confirmation"
                        v-model="form.password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        placeholder="Repita a nova senha"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="open = false"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing" data-test="update-password-dialog-button">
                        {{ form.processing ? 'Salvando…' : 'Salvar senha' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
