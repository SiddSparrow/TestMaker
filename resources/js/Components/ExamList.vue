<template>
    <div class="space-y-4">
        <Link v-for="exam in exams" :key="exam.id"
              :href="route('exams.show', exam.id)"
              class="block p-4 border border-gray-100 rounded-lg hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            <div class="flex justify-between items-start">
                <h4 class="font-semibold text-gray-800">{{ exam.title }}</h4>
                <span class="text-xs text-gray-500">{{ exam.questions_count }} questões</span>
            </div>
            <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ exam.description }}</p>
            <div class="mt-4 flex justify-between items-center">
                <span class="text-sm text-gray-500">{{ exam.created_at }}</span>
                <span class="text-sm font-medium text-blue-600">{{ exam.total_points }} pontos</span>
            </div>
        </Link>

        <div v-if="exams.length === 0" class="text-center py-8 text-gray-500">
            Nenhuma prova encontrada
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    exams: {
        type: Array,
        default: () => []
    }
});
</script>