<template>
    <div class="grid grid-cols-12 gap-6">
        <!-- COLUNA ESQUERDA: Banco de Questões -->
        <div class="col-span-5">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Header do Banco -->
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Banco de Questões
                        </h3>
                        <span class="text-sm text-gray-600">
                            {{ availableQuestions.length }} disponíveis
                        </span>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="space-y-2">
                        <input type="text"
                               v-model="searchQuery"
                               placeholder="Buscar por enunciado..."
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        
                        <div class="grid grid-cols-2 gap-2">
                            <select v-model="filterSubject"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todas as matérias</option>
                                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                    {{ subject.name }}
                                </option>
                            </select>
                            
                            <select v-model="filterDifficulty"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todas dificuldades</option>
                                <option value="easy">Fácil</option>
                                <option value="medium">Médio</option>
                                <option value="hard">Difícil</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Lista de Questões Disponíveis -->
                <div class="p-4 max-h-[600px] overflow-y-auto">
                    <draggable
                        :list="filteredQuestions"
                        :group="{ name: 'questions', pull: 'clone', put: false }"
                        :clone="cloneQuestion"
                        :sort="false"
                        item-key="id"
                        class="space-y-3">
                        <template #item="{ element: question }">
                            <div class="p-3 border border-gray-200 rounded-lg hover:border-blue-400 hover:shadow-md transition-all cursor-move bg-white"
                                 :class="{ 'opacity-50': isQuestionInExam(question.id) }">
                                <div class="flex items-start gap-3">
                                    <!-- Ícone de Drag -->
                                    <div class="text-gray-400 mt-1">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2zm0-4a1 1 0 100-2 1 1 0 000 2zm0-4a1 1 0 100-2 1 1 0 000 2z"/>
                                        </svg>
                                    </div>
                                    
                                    <!-- Conteúdo -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 line-clamp-2 mb-2">
                                            {{ question.statement }}
                                        </p>
                                        
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <!-- Badge Matéria -->
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ getSubjectName(question.subject_id) }}
                                            </span>
                                            
                                            <!-- Badge Tipo -->
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                {{ getQuestionTypeName(question.question_type_id) }}
                                            </span>
                                            
                                            <!-- Badge Dificuldade -->
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                  :class="getDifficultyClass(question.difficulty_level)">
                                                {{ getDifficultyLabel(question.difficulty_level) }}
                                            </span>
                                            
                                            <!-- Pontos -->
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ question.points }} pts
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Overlay se já está na prova -->
                                <div v-if="isQuestionInExam(question.id)" 
                                     class="absolute inset-0 bg-gray-100 bg-opacity-50 rounded-lg flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600 bg-white px-2 py-1 rounded shadow-sm">
                                        ✓ Já adicionada
                                    </span>
                                </div>
                            </div>
                        </template>
                    </draggable>
                    
                    <!-- Mensagem quando não há questões -->
                    <div v-if="filteredQuestions.length === 0" 
                         class="text-center py-12 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-2 text-sm">Nenhuma questão encontrada</p>
                        <p class="text-xs text-gray-400">Ajuste os filtros ou crie novas questões</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUNA DIREITA: Prova sendo Montada -->
        <div class="col-span-7">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Header da Prova -->
                <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ examTitle || 'Nova Prova' }}
                            </h3>
                            <p class="text-sm text-gray-600">
                                Arraste questões para adicionar à prova
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ currentTotalPoints }}
                            </div>
                            <div class="text-xs text-gray-600">
                                {{ targetPoints ? `de ${targetPoints} pontos` : 'pontos' }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar de Pontos -->
                    <div v-if="targetPoints" class="relative w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="absolute top-0 left-0 h-full transition-all duration-300 rounded-full"
                             :class="pointsProgressClass"
                             :style="{ width: `${pointsProgress}%` }"></div>
                    </div>
                </div>

                <!-- Lista de Questões da Prova -->
                <div class="p-4 min-h-[600px]">
                    <draggable
                        v-model="localExamQuestions"
                        group="questions"
                        item-key="id"
                        handle=".drag-handle"
                        @end="handleReorder"
                        class="space-y-3">
                        <template #item="{ element: question, index }">
                            <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-blue-300 transition-all bg-white shadow-sm">
                                <div class="flex items-start gap-3">
                                    <!-- Handle de Drag -->
                                    <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600 mt-1">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2zm0-4a1 1 0 100-2 1 1 0 000 2zm0-4a1 1 0 100-2 1 1 0 000 2z"/>
                                        </svg>
                                    </div>
                                    
                                    <!-- Número da Questão -->
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-sm">
                                            {{ index + 1 }}
                                        </div>
                                    </div>
                                    
                                    <!-- Conteúdo -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 mb-2">
                                            {{ question.statement }}
                                        </p>
                                        
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ getSubjectName(question.subject_id) }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                {{ getQuestionTypeName(question.question_type_id) }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                  :class="getDifficultyClass(question.difficulty_level)">
                                                {{ getDifficultyLabel(question.difficulty_level) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Pontos e Ações -->
                                    <div class="flex items-start gap-2">
                                        <input type="number"
                                               v-model.number="question.points_override"
                                               @input="updatePoints"
                                               min="1"
                                               max="10"
                                               class="w-16 px-2 py-1 text-sm text-center border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"
                                               :placeholder="question.points.toString()">
                                        
                                        <button @click="removeQuestion(index)"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"
                                                title="Remover questão">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </draggable>
                    
                    <!-- Estado Vazio -->
                    <div v-if="localExamQuestions.length === 0" 
                         class="flex flex-col items-center justify-center h-full py-16 text-gray-400">
                        <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-lg font-medium mb-1">Prova vazia</p>
                        <p class="text-sm">Arraste questões do banco para começar</p>
                    </div>
                </div>

                <!-- Footer com Resumo -->
                <div v-if="localExamQuestions.length > 0" 
                     class="p-4 border-t border-gray-200 bg-gray-50">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ localExamQuestions.length }}
                            </div>
                            <div class="text-xs text-gray-600">Questões</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ currentTotalPoints }}
                            </div>
                            <div class="text-xs text-gray-600">Pontos Totais</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600">
                                {{ averagePoints.toFixed(1) }}
                            </div>
                            <div class="text-xs text-gray-600">Média por Questão</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    availableQuestions: {
        type: Array,
        required: true
    },
    subjects: {
        type: Array,
        default: () => []
    },
    questionTypes: {
        type: Array,
        default: () => []
    },
    examTitle: {
        type: String,
        default: ''
    },
    targetPoints: {
        type: Number,
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'reorder', 'points-change']);

// Estados locais
const localExamQuestions = ref([...props.modelValue]);
const searchQuery = ref('');
const filterSubject = ref('');
const filterDifficulty = ref('');

// Sincroniza com v-model
watch(() => props.modelValue, (newVal) => {
    localExamQuestions.value = [...newVal];
}, { deep: true });

watch(localExamQuestions, (newVal) => {
    emit('update:modelValue', newVal);
}, { deep: true });

// Computed: Questões filtradas
const filteredQuestions = computed(() => {
    let filtered = props.availableQuestions;
    
    if (searchQuery.value) {
        filtered = filtered.filter(q => 
            q.statement.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    
    if (filterSubject.value) {
        filtered = filtered.filter(q => q.subject_id == filterSubject.value);
    }
    
    if (filterDifficulty.value) {
        filtered = filtered.filter(q => q.difficulty_level === filterDifficulty.value);
    }
    
    return filtered;
});

// Computed: Total de pontos atual
const currentTotalPoints = computed(() => {
    return localExamQuestions.value.reduce((total, q) => {
        return total + (q.points_override || q.points);
    }, 0);
});

// Computed: Média de pontos
const averagePoints = computed(() => {
    if (localExamQuestions.value.length === 0) return 0;
    return currentTotalPoints.value / localExamQuestions.value.length;
});

// Computed: Progresso de pontos
const pointsProgress = computed(() => {
    if (!props.targetPoints) return 0;
    return Math.min((currentTotalPoints.value / props.targetPoints) * 100, 100);
});

// Computed: Classe da barra de progresso
const pointsProgressClass = computed(() => {
    const progress = pointsProgress.value;
    if (progress >= 100) return 'bg-green-500';
    if (progress >= 75) return 'bg-blue-500';
    if (progress >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
});

// Verifica se questão já está na prova
const isQuestionInExam = (questionId) => {
    return localExamQuestions.value.some(q => q.id === questionId);
};

// Clona questão para adicionar na prova
const cloneQuestion = (original) => {
    return {
        ...original,
        points_override: null,
        exam_order: localExamQuestions.value.length + 1
    };
};

// Remove questão da prova
const removeQuestion = (index) => {
    localExamQuestions.value.splice(index, 1);
    updateOrder();
};

// Atualiza ordem após drag
const handleReorder = () => {
    localExamQuestions.value.forEach((q, index) => {
        q.exam_order = index + 1;
    });
    emit('reorder', localExamQuestions.value);
};

// Atualiza ordem manualmente
const updateOrder = () => {
    handleReorder();
};

// Atualiza pontos
const updatePoints = () => {
    emit('points-change', currentTotalPoints.value);
};

// Helpers
const getSubjectName = (subjectId) => {
    return props.subjects.find(s => s.id === subjectId)?.name || 'N/A';
};

const getQuestionTypeName = (typeId) => {
    return props.questionTypes.find(t => t.id === typeId)?.name || 'N/A';
};

const getDifficultyLabel = (level) => {
    const labels = { easy: 'Fácil', medium: 'Médio', hard: 'Difícil' };
    return labels[level] || level;
};

const getDifficultyClass = (level) => {
    const classes = {
        easy: 'bg-green-100 text-green-800',
        medium: 'bg-yellow-100 text-yellow-800',
        hard: 'bg-red-100 text-red-800'
    };
    return classes[level] || 'bg-gray-100 text-gray-800';
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Animação de drag */
.sortable-ghost {
    opacity: 0.5;
    background: #e0f2fe;
}

.sortable-drag {
    opacity: 0.9;
}
</style>