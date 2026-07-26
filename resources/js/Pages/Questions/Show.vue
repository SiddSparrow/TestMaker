<template>
    <AppLayout>
        <Head title="Detalhes da Questão" />

        <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Detalhes da Questão</h1>
                    <p class="mt-2 text-sm text-gray-600">Visualização completa da questão</p>
                </div>
                <div class="flex space-x-3">
                    <Link :href="route('questions.edit', question.id)"
                       class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-md transition-colors">
                        Editar
                    </Link>
                    <Link :href="route('questions.index')"
                       class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition-colors">
                        Voltar
                    </Link>
                </div>
            </div>

            <!-- Informações Básicas -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informações Básicas</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Matéria</label>
                        <p class="text-gray-900">{{ question.subject?.name || '-' }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Tópico</label>
                        <p class="text-gray-900">{{ question.topic?.name || 'Não definido' }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Tipo de Questão</label>
                        <p class="text-gray-900">{{ question.question_type?.name || '-' }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Dificuldade</label>
                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium"
                              :class="{
                                  'bg-green-100 text-green-700': question.difficulty_level === 'easy',
                                  'bg-yellow-100 text-yellow-700': question.difficulty_level === 'medium',
                                  'bg-red-100 text-red-700': question.difficulty_level === 'hard'
                              }">
                            {{ getDifficultyLabel(question.difficulty_level) }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Pontos</label>
                        <p class="text-gray-900 font-semibold">{{ question.points }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium"
                              :class="question.is_active 
                                  ? 'bg-green-100 text-green-700' 
                                  : 'bg-gray-100 text-gray-700'">
                            {{ question.is_active ? 'Ativa' : 'Inativa' }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-500">
                        <div>
                            <span class="font-medium">Criada por:</span> {{ question.user?.name || '-' }}
                        </div>
                        <div>
                            <span class="font-medium">Data de criação:</span> {{ formatDate(question.created_at) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enunciado -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Enunciado</h2>
                <div class="prose max-w-none">
                    <p class="text-gray-900 whitespace-pre-wrap">{{ question.statement }}</p>
                </div>
            </div>

            <!-- Alternativas -->
            <div v-if="question.alternatives?.length > 0" 
                 class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ isVerdadeiroFalso ? 'Afirmações (Verdadeiro/Falso)' : 'Alternativas' }}
                </h2>
                
                <div class="space-y-3">
                    <div v-for="(alternative, index) in sortedAlternatives" 
                         :key="alternative.id"
                         class="flex items-start space-x-3 p-4 rounded-lg border-2 transition-colors"
                         :class="alternative.is_correct 
                             ? 'border-green-500 bg-green-50' 
                             : 'border-gray-200 bg-white'">
                        
                        <!-- Indicador correto/incorreto -->
                        <div class="flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg v-if="alternative.is_correct" 
                                 class="w-6 h-6 text-green-600" 
                                 fill="currentColor" 
                                 viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <svg v-else 
                                 class="w-6 h-6 text-gray-400" 
                                 fill="none" 
                                 stroke="currentColor" 
                                 viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            </svg>
                        </div>

                        <!-- Letra/Número da alternativa -->
                        <div class="flex items-center justify-center w-8 h-8 rounded-full font-semibold flex-shrink-0"
                             :class="alternative.is_correct 
                                 ? 'bg-green-600 text-white' 
                                 : 'bg-blue-100 text-blue-700'">
                            {{ isVerdadeiroFalso ? (index + 1) : String.fromCharCode(65 + index) }}
                        </div>

                        <!-- Conteúdo -->
                        <div class="flex-1 pt-1">
                            <p class="text-gray-900">{{ alternative.content }}</p>
                            <span v-if="alternative.is_correct" 
                                  class="inline-block mt-2 text-xs font-medium text-green-700">
                                {{ isVerdadeiroFalso ? '✓ Verdadeiro' : '✓ Resposta correta' }}
                            </span>
                            <span v-else-if="isVerdadeiroFalso" 
                                  class="inline-block mt-2 text-xs font-medium text-red-700">
                                ✗ Falso
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Explicação -->
            <div v-if="question.explanation" 
                 class="bg-blue-50 rounded-lg border border-blue-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-blue-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    Explicação/Resolução
                </h2>
                <div class="prose max-w-none">
                    <p class="text-blue-900 whitespace-pre-wrap">{{ question.explanation }}</p>
                </div>
            </div>

            <!-- Etiquetas -->
            <div v-if="question.tags?.length > 0"
                 class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Etiquetas</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="tag in question.tags"
                          :key="tag.id"
                          class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                        {{ tag.name }}
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    question: Object,
});

// Ordena alternativas por ordem
const sortedAlternatives = computed(() => {
    if (!props.question.alternatives) return [];
    return [...props.question.alternatives].sort((a, b) => a.order - b.order);
});

// Verifica se é tipo verdadeiro/falso
const isVerdadeiroFalso = computed(() => {
    return props.question.question_type?.slug === 'verdadeiro-falso';
});

// Formata data
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Label de dificuldade
const getDifficultyLabel = (level) => {
    const labels = {
        'easy': 'Fácil',
        'medium': 'Médio',
        'hard': 'Difícil'
    };
    return labels[level] || level;
};

</script>