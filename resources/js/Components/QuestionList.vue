<template>
    <div class="space-y-4">
        <div v-for="question in questions" :key="question.id"
             class="p-3 border border-gray-100 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
             @click="$inertia.visit(route('questions.edit', question.id))">
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
                <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                          'bg-green-100 text-green-800': question.difficulty_level === 'easy',
                          'bg-yellow-100 text-yellow-800': question.difficulty_level === 'medium',
                          'bg-red-100 text-red-800': question.difficulty_level === 'hard'
                      }">
                    {{ question.difficulty_level }}
                </span>
            </div>
            <div class="mt-2 flex justify-between text-xs text-gray-500">
                <span>{{ question.points }} ponto{{ question.points !== 1 ? 's' : '' }}</span>
                <span>{{ question.created_at }}</span>
            </div>
        </div>
        
        <div v-if="questions.length === 0" class="text-center py-8 text-gray-500">
            Nenhuma questão encontrada
        </div>
    </div>
</template>

<script setup>
defineProps({
    questions: {
        type: Array,
        default: () => []
    }
});
</script>