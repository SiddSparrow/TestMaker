<template>
    <div class="space-y-4">
        <Link v-for="question in questions" :key="question.id"
              :href="route('questions.show', question.id)"
              class="block p-3 border border-gray-100 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        {{ question.statement }}
                    </p>
                    <div class="mt-2 flex items-center space-x-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                              :style="{ backgroundColor: question.subject.color + '20', color: question.subject.color }">
                            {{ question.subject.name }}
                        </span>
                        <span class="text-xs text-gray-500">{{ question.topic || 'Sem tópico' }}</span>
                    </div>
                </div>
                <DifficultyBadge class="ml-3 shrink-0" :level="question.difficulty_level" />
            </div>
            <div class="mt-2 flex justify-between text-xs text-gray-500">
                <span>{{ question.points }} ponto{{ question.points !== 1 ? 's' : '' }}</span>
                <span>{{ question.created_at }}</span>
            </div>
        </Link>

        <div v-if="questions.length === 0" class="text-center py-8 text-gray-500">
            Nenhuma questão encontrada
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import DifficultyBadge from '@/Components/UI/DifficultyBadge.vue';

defineProps({
    questions: {
        type: Array,
        default: () => []
    }
});
</script>