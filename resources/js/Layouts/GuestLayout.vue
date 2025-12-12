<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <Link :href="route('dashboard')">
                <ApplicationLogo class="w-20 h-20 fill-current text-gray-800" />
            </Link>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-lg overflow-hidden sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>

<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>