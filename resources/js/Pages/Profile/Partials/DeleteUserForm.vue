<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Excluir Conta
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Depois que sua conta for excluída, todos os seus recursos e dados
                serão apagados permanentemente. Antes de excluir sua conta, baixe
                qualquer dado ou informação que deseje manter.
            </p>
        </header>

        <BaseButton variant="danger" @click="confirmUserDeletion">Excluir Conta</BaseButton>

        <AppModal :show="confirmingUserDeletion" title="Tem certeza que deseja excluir sua conta?" @close="closeModal">
            <p class="mt-1 text-sm text-gray-600">
                Depois que sua conta for excluída, todos os seus recursos e
                dados serão apagados permanentemente. Digite sua senha para
                confirmar que deseja excluir sua conta definitivamente.
            </p>

            <div class="mt-6">
                <InputLabel
                    for="password"
                    value="Senha"
                    class="sr-only"
                />

                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Senha"
                    @keyup.enter="deleteUser"
                />

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <BaseButton variant="secondary" @click="closeModal">
                    Cancelar
                </BaseButton>

                <BaseButton
                    variant="danger"
                    :disabled="form.processing"
                    :loading="form.processing"
                    @click="deleteUser"
                >
                    Excluir Conta
                </BaseButton>
            </div>
        </AppModal>
    </section>
</template>
