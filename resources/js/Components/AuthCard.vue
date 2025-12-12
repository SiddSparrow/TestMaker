<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50">
        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="flex justify-center mb-6">
                <Link :href="route('dashboard')">
                    <ApplicationLogo class="w-20 h-20 fill-current text-gray-800" />
                </Link>
            </div>
            
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">
                    <slot name="title">Autenticação</slot>
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    <slot name="description">
                        Faça login para acessar sua conta
                    </slot>
                </p>
            </div>

            <slot />
        </div>
    </div>
</template>

<script setup>
import ApplicationLogo from './ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>