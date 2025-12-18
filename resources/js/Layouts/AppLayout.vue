<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const { props } = usePage();
const user = computed(() => props.auth.user);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-md border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo ou Nome do Sistema -->
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-800"><a href="/dashboard">TestMaker</a></h1>
                    </div>

                    <!-- User Info e Logout -->
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-700">{{ user?.name }}</p>
                            <p class="text-xs text-gray-500">{{ user?.email }}</p>
                        </div>
                        
                        <div class="relative">
                            <button
                                @click="logout"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Sair
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <Transition name="page-transition" mode="out-in">
                <div>
                    <slot />
                </div>
                
            </Transition>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t mt-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        &copy; {{ new Date().getFullYear() }} TestMaker. Todos os direitos reservados.
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Sistema de gerenciamento de questões e provas
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.page-transition-enter-active,
.page-transition-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.page-transition-enter-from,
.page-transition-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>