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
                <div class="w-16 h-16 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>

            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">TestMaker</h2>
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
defineProps({
    subtitle: {
        type: String,
        default: 'Sistema de criação e gestão de provas',
    },
});
</script>
