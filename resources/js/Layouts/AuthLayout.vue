<!--
    Chrome comum das telas de autenticação (fundo em gradiente, logo, card
    branco). Login.vue já tinha esse visual; as outras cinco telas
    (Register/ForgotPassword/ResetPassword/ConfirmPassword/VerifyEmail)
    usavam o GuestLayout do Breeze puro — fluxo de cadastro "parecia de
    outro produto" e ainda estava em inglês.
-->
<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-gray-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <ApplicationLogo class="w-16 h-16" />
            </div>

            <h1 class="mt-6 text-center text-3xl font-extrabold tracking-tight">
                <span class="text-gray-900">test</span><span class="text-primary-600">maker</span>
            </h1>
            <p class="mt-2 text-center text-sm text-gray-600">{{ subtitle }}</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-6 shadow-lg rounded-xl sm:px-10">
                <slot />
            </div>

            <div class="mt-8 text-center">
                <p class="text-xs text-gray-500">
                    &copy; {{ new Date().getFullYear() }} TestMaker. Sistema de gestão educacional.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    subtitle: {
        type: String,
        default: 'Sistema de criação e gestão de provas',
    },
});
</script>
