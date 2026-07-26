<script setup>
import { computed } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <Head title="Verificar E-mail" />

    <AuthLayout subtitle="Confirme seu endereço de e-mail">
        <div class="mb-4 text-sm text-gray-600">
            Obrigado por se cadastrar! Antes de começar, você pode confirmar seu
            e-mail clicando no link que acabamos de enviar? Se não recebeu o
            e-mail, teremos prazer em enviar outro.
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            Um novo link de verificação foi enviado para o e-mail informado no
            cadastro.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    class="!bg-blue-600 hover:!bg-blue-700 focus:!ring-blue-500 active:!bg-blue-700"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Reenviar E-mail de Verificação
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >Sair</Link
                >
            </div>
        </form>
    </AuthLayout>
</template>
