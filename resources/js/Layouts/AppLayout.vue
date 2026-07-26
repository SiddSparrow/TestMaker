<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ToastHost from '@/Components/UI/ToastHost.vue';

const { props } = usePage();
const user = computed(() => props.auth.user);

const showingNavigationDropdown = ref(false);
const isCatalogActive = computed(() =>
    route().current('subjects.*') || route().current('topics.*') || route().current('tags.*')
);

const logout = () => {
    router.post(route('logout'));
};

// Foco visível some ao trocar de página (o <main> não é um elemento
// focável por padrão) — usuário de teclado ficava sem indicação de onde
// o foco estava depois de uma navegação. router.on('navigate') só dispara
// numa troca de página de verdade (não em toda requisição/reload parcial).
const mainRef = ref(null);
router.on('navigate', () => {
    mainRef.value?.focus();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <a href="#main-content"
           class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-2 focus:left-2 focus:bg-white focus:text-gray-900 focus:px-4 focus:py-2 focus:rounded-md focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            Pular para o conteúdo
        </a>
        <!-- Navegação principal — antes só existia no AuthenticatedLayout usado
             pelo Perfil; sem isso, sair de uma tela interna exigia voltar ao
             Dashboard a cada troca de contexto. -->
        <header class="bg-white shadow-md border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-2">
                                <ApplicationLogo class="block h-8 w-auto fill-current text-gray-800" />
                                <span class="text-lg font-bold text-gray-800">TestMaker</span>
                            </Link>
                        </div>

                        <div class="hidden space-x-1 sm:-my-px sm:ml-10 sm:flex sm:items-center">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavLink>
                            <NavLink :href="route('questions.index')" :active="route().current('questions.*')">
                                Questões
                            </NavLink>
                            <NavLink :href="route('exams.index')" :active="route().current('exams.*')">
                                Provas
                            </NavLink>
                            <NavLink :href="route('documents.index')" :active="route().current('documents.*')">
                                Documentos
                            </NavLink>
                            <Dropdown align="left" width="48">
                                <template #trigger="{ open }">
                                    <button
                                        type="button"
                                        aria-haspopup="true"
                                        :aria-expanded="open"
                                        class="group flex items-center gap-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                                        :class="isCatalogActive
                                            ? 'bg-primary-50 text-primary-700 border-l-4 border-primary-600 pl-2.5'
                                            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'"
                                    >
                                        Cadastros
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('subjects.index')">Matérias</DropdownLink>
                                    <DropdownLink :href="route('topics.index')">Tópicos</DropdownLink>
                                    <DropdownLink :href="route('tags.index')">Etiquetas</DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <Dropdown align="right" width="48">
                            <template #trigger="{ open }">
                                <button
                                    type="button"
                                    aria-haspopup="true"
                                    :aria-expanded="open"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                >
                                    {{ user?.name }}
                                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Sair
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Hamburger -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                            :aria-expanded="showingNavigationDropdown"
                            aria-label="Abrir menu de navegação"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Menu de navegação responsivo -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('questions.index')" :active="route().current('questions.*')">
                        Questões
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('exams.index')" :active="route().current('exams.*')">
                        Provas
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('documents.index')" :active="route().current('documents.*')">
                        Documentos
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('subjects.index')" :active="route().current('subjects.*')">
                        Matérias
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('topics.index')" :active="route().current('topics.*')">
                        Tópicos
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('tags.index')" :active="route().current('tags.*')">
                        Etiquetas
                    </ResponsiveNavLink>
                </div>

                <div class="pt-4 pb-1 border-t border-gray-200">
                    <div class="px-4">
                        <div class="font-medium text-base text-gray-800">{{ user?.name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ user?.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Perfil</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Sair
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </header>

        <!-- Cabeçalho opcional da página (título, breadcrumb, ações) -->
        <header v-if="$slots.header" class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <main id="main-content" ref="mainRef" tabindex="-1" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 focus:outline-none">
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

    <ToastHost />
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
