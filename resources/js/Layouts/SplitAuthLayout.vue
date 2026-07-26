<!--
    Layout de autenticação em duas colunas (painel institucional + card de
    formulário), usado hoje só pelo Login. As outras telas de auth
    (Register/ForgotPassword/ResetPassword/ConfirmPassword/VerifyEmail)
    continuam no AuthLayout compartilhado — dá pra migrar depois se quiser
    consistência visual entre todas.
-->
<template>
    <div class="min-h-screen lg:grid lg:grid-cols-2 bg-white">
        <!-- Painel institucional -->
        <div class="hidden lg:flex flex-col justify-center px-16 py-12 bg-gray-50 relative overflow-hidden">
            <div class="max-w-md">
                <!-- Logo -->
                <div class="flex items-center gap-2.5 mb-12">
                    <div class="w-9 h-9 rounded-lg border-2 border-primary-600 bg-white flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight">
                        <span class="text-gray-900">test</span><span class="text-primary-600">maker</span>
                    </span>
                </div>

                <!-- Headline -->
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 leading-tight">
                    Crie provas melhores.<br />
                    Em <span class="text-primary-600">menos tempo</span>.
                </h1>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    O Testmaker ajuda professores a criarem provas e questões com mais
                    agilidade, usando um banco de questões inteligente e organizado.
                </p>

                <!-- Features -->
                <ul class="mt-10 space-y-6">
                    <li v-for="feature in features" :key="feature.title" class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="feature.icon" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ feature.title }}</p>
                            <p class="text-sm text-gray-600 mt-0.5">{{ feature.description }}</p>
                        </div>
                    </li>
                </ul>

                <!-- Mockup ilustrativo -->
                <div class="mt-12 rounded-xl bg-white border border-gray-200 shadow-lg p-4" aria-hidden="true">
                    <div class="flex items-center gap-1.5 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-200"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-200"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-200"></span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="h-12 rounded-md bg-primary-50 border border-primary-100"></div>
                        <div class="h-12 rounded-md bg-gray-50 border border-gray-100"></div>
                        <div class="h-12 rounded-md bg-gray-50 border border-gray-100"></div>
                    </div>
                    <div class="space-y-1.5">
                        <div class="h-2 rounded-full bg-gray-100 w-full"></div>
                        <div class="h-2 rounded-full bg-gray-100 w-4/5"></div>
                        <div class="h-2 rounded-full bg-gray-100 w-3/5"></div>
                    </div>
                </div>
            </div>

            <!-- Elementos decorativos ao fundo -->
            <div class="pointer-events-none absolute -bottom-10 -left-10 w-40 h-40 rounded-full bg-primary-100/40 blur-2xl"></div>
            <div class="pointer-events-none absolute top-10 -right-10 w-48 h-48 rounded-full bg-primary-50 blur-2xl"></div>
        </div>

        <!-- Card do formulário -->
        <div class="flex items-center justify-center px-4 py-12 sm:px-6 lg:px-16 bg-white">
            <div class="w-full max-w-sm">
                <!-- Logo (só aparece em telas pequenas, onde o painel some) -->
                <div class="flex lg:hidden items-center justify-center gap-2 mb-8">
                    <div class="w-8 h-8 rounded-lg border-2 border-primary-600 bg-white flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold tracking-tight">
                        <span class="text-gray-900">test</span><span class="text-primary-600">maker</span>
                    </span>
                </div>

                <h2 class="text-2xl font-bold text-gray-900">{{ title }}</h2>
                <p class="mt-1.5 text-sm text-gray-600">{{ subtitle }}</p>

                <div class="mt-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: 'Bem-vindo de volta!',
    },
    subtitle: {
        type: String,
        default: 'Faça login para acessar sua conta',
    },
});

const features = [
    {
        title: 'Banco de questões completo',
        description: 'Organize e reutilize questões por disciplina, tema, dificuldade e muito mais.',
        icon: 'M13 10V3L4 14h7v7l9-11h-7z',
    },
    {
        title: 'Criação de provas acelerada',
        description: 'Monte provas personalizadas em poucos cliques com filtros inteligentes.',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        title: 'Mais tempo para o que importa',
        description: 'Reduza o tempo gasto com tarefas repetitivas e foque no ensino.',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    },
];
</script>
