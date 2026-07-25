<template>
    <AppLayout>
        <Head title="Editar Questão" />

        <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

            <div v-if="Object.keys(form.errors).length > 0"
                 class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <h3 class="text-red-800 font-bold mb-2">⚠️ Erros de Validação:</h3>
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    <li v-for="(error, field) in form.errors" :key="field">
                        <strong>{{ field }}:</strong> {{ error }}
                    </li>
                </ul>
            </div>
            <CopySuccessBanner 
                    :show="isCopy"
                    :original-id="originalQuestionId"
                    original-route="questions.show"
                    :original-label="'Questão original'"
                    @dismiss="isCopy = false"
                />
            <div class="mb-6 flex items-center justify-between">
                
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Editar Questão</h1>
                    <p class="mt-2 text-sm text-gray-600">Atualize os campos necessários</p>
                </div>
                <div class="flex space-x-3">
                    <!-- BOTÃO DE CRIAR CÓPIA -->
                    <button @click="createCopy"
                            :disabled="isCopying"
                            class="relative px-4 py-2 text-sm rounded-md transition-all duration-200 flex items-center gap-2"
                            :class="isCopying 
                                ? 'bg-green-500 text-white shadow-inner cursor-wait' 
                                : 'bg-green-600 text-white hover:bg-green-700 hover:shadow-md'">
                        
                        <!-- Checkmark que aparece durante o loading -->
                        <div class="relative h-4 w-4">
                            <svg v-if="!isCopying" class="absolute inset-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            
                            <svg v-if="isCopying" 
                                class="absolute inset-0 text-white animate-checkmark" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        
                        <span class="font-medium transition-all duration-200"
                            :class="isCopying ? 'translate-x-1' : ''">
                            {{ isCopying ? 'Criando Cópia!' : 'Criar Cópia' }}
                        </span>
                        
                        <!-- Seta que aparece durante o loading -->
                        <svg v-if="isCopying" 
                            class="h-3 w-3 ml-1 animate-bounce-right" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </div>
            

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Informações Básicas -->
                <QuestionFormFields
                        v-model:subject-id="form.subject_id"
                        v-model:topic-id="form.topic_id"
                        v-model:question-type-id="form.question_type_id"
                        v-model:difficulty-level="form.difficulty_level"
                        v-model:points="form.points"
                        v-model:is-active="form.is_active"
                        :subjects="subjects"
                        :topics="topics"
                        :question-types="question_types"
                        :errors="form.errors"
                        @subject-change="onSubjectChange"
                        @question-type-change="onQuestionTypeChange"
                    />

                <!-- Enunciado -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Enunciado</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Enunciado da questão <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="form.statement"
                                  rows="5"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  :class="{ 'border-red-500': form.errors.statement }"
                                  placeholder="Digite o enunciado da questão..."></textarea>
                        <p v-if="form.errors.statement" class="mt-1 text-sm text-red-600">
                            {{ form.errors.statement }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ form.statement.length }} caracteres (mínimo 10)
                        </p>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Explicação/Resolução
                        </label>
                        <textarea v-model="form.explanation"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Opcional: explicação da resposta correta..."></textarea>
                    </div>
                </div>

                <!-- Alternativas (apenas para tipos específicos) -->
                <AlternativesManager
                    v-if="showAlternatives"
                    v-model="form.alternatives"
                    :question-type="currentQuestionType"
                    :errors="form.errors"
                />

                <!-- Tags -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Tags</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Adicionar tags (opcional)
                        </label>
                        <select v-model="selectedTag"
                                @change="addTag"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione uma tag</option>
                            <option v-for="tag in availableTags" 
                                    :key="tag.id" 
                                    :value="tag.id">
                                {{ tag.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="form.tags.length > 0" class="mt-3 flex flex-wrap gap-2">
                        <span v-for="tagId in form.tags"
                              :key="tagId"
                              class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                            {{ getTagName(tagId) }}
                            <button type="button"
                                    @click="removeTag(tagId)"
                                    class="ml-2 text-blue-600 hover:text-blue-800">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </span>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex justify-end space-x-3 pt-6">
                    <a :href="route('questions.show', question.id)"
                       class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-blue-600 text-white hover:bg-blue-700 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Salvando...' : 'Atualizar Questão' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CopySuccessBanner from '@/Components/CopySuccessBanner.vue';
import AlternativesManager from '@/Components/AlternativesManager.vue';
import QuestionFormFields from '@/Components/QuestionFormFields.vue';

const props = defineProps({
    question: Object,
    subjects: Array,
    topics: Array,
    question_types: Array,
    tags: Array,
});

const form = useForm({
    statement: '',
    explanation: '',
    subject_id: '',
    topic_id: '',
    question_type_id: '',
    difficulty_level: 'medium',
    points: 1,
    is_active: true,
    alternatives: [],
    tags: [],
});

const copyForm = useForm({
    statement: '',
    explanation: '',
    subject_id: '',
    topic_id: '',
    question_type_id: '',
    difficulty_level: 'medium',
    points: 1,
    is_active: true,
    alternatives: [],
    tags: [],
    copy_from_id: null,
});
const isCopying = ref(false);
const isCopy = ref(props.is_copy || false);
const originalQuestionId = ref(props.original_question_id);
const selectedTag = ref('');

// Inicializa o formulário com os dados da questão
onMounted(() => {
    form.statement = props.question.statement || '';
    form.explanation = props.question.explanation || '';
    form.subject_id = props.question.subject_id || '';
    form.topic_id = props.question.topic_id || '';
    form.question_type_id = props.question.question_type_id || '';
    form.difficulty_level = props.question.difficulty_level || 'medium';
    form.points = props.question.points || 1;
    form.is_active = props.question.is_active ?? true;
    
    // Carrega alternativas
    if (props.question.alternatives && props.question.alternatives.length > 0) {
        form.alternatives = props.question.alternatives.map(alt => ({
            id: alt.id,
            content: alt.content,
            is_correct: alt.is_correct,
            order: alt.order
        }));
    }
    
    // Carrega tags
    if (props.question.tags && props.question.tags.length > 0) {
        form.tags = props.question.tags.map(tag => tag.id);
    }
});


// Verifica se deve mostrar alternativas
const showAlternatives = computed(() => {
    const type = props.question_types.find(t => t.id === form.question_type_id);
    if (!type) return false;
    
    const slug = type.slug.toLowerCase();
    return ['multipla-escolha', 'verdadeiro-falso', 'multipla-resposta'].includes(slug);
});

const currentQuestionType = computed(() => {
    return props.question_types.find(t => t.id === form.question_type_id) || {};
});

// Verifica se é tipo múltipla resposta
const isMultipleAnswer = computed(() => {
    const type = props.question_types.find(t => t.id === form.question_type_id);
    return type?.slug === 'multipla-resposta';
});

// Verifica se é tipo verdadeiro/falso
const isVerdadeiroFalso = computed(() => {
    const type = props.question_types.find(t => t.id === form.question_type_id);
    return type?.slug === 'verdadeiro-falso';
});

// Verifica se há resposta correta marcada
const hasCorrectAnswer = computed(() => {
    return form.alternatives.some(alt => alt.is_correct);
});

// Tags disponíveis (não selecionadas)
const availableTags = computed(() => {
    return props.tags.filter(tag => !form.tags.includes(tag.id));
});



// Quando muda o tipo de questão
const onQuestionTypeChange = () => {
    const type = props.question_types.find(t => t.id === form.question_type_id);
    
    if (type?.slug === 'dissertativa') {
        // Dissertativa não tem alternativas
        form.alternatives = [];
    } else if (form.alternatives.length === 0) {
        // Outros tipos começam com 2 alternativas vazias
        form.alternatives = [
            { content: '', is_correct: false, order: 1 },
            { content: '', is_correct: false, order: 2 },
        ];
    }
};

// Adiciona alternativa
const addAlternative = () => {
    form.alternatives.push({
        content: '',
        is_correct: false,
        order: form.alternatives.length + 1,
    });
};

// Remove alternativa
const removeAlternative = (index) => {
    form.alternatives.splice(index, 1);
    // Reordena
    form.alternatives.forEach((alt, i) => {
        alt.order = i + 1;
    });
};

// Define resposta correta (radio button)
const setCorrectAnswer = (index) => {
    form.alternatives.forEach((alt, i) => {
        alt.is_correct = i === index;
    });
};

// Adiciona tag
const addTag = () => {
    if (selectedTag.value && !form.tags.includes(selectedTag.value)) {
        form.tags.push(selectedTag.value);
        selectedTag.value = '';
    }
};

// Remove tag
const removeTag = (tagId) => {
    const index = form.tags.indexOf(tagId);
    if (index > -1) {
        form.tags.splice(index, 1);
    }
};

// Pega nome da tag pelo ID
const getTagName = (tagId) => {
    return props.tags.find(tag => tag.id === tagId)?.name || '';
};

// Submit do formulário
const submit = () => {
    form.put(route('questions.update', props.question.id), {
        preserveScroll: true,
        // Erros são automaticamente injetados em form.errors.
    });
};

const createCopy = () => {
    if (isCopying.value) return;

    isCopying.value = true;

    copyForm.statement = form.statement;
    copyForm.explanation = form.explanation;
    copyForm.subject_id = form.subject_id;
    copyForm.topic_id = form.topic_id;
    copyForm.question_type_id = form.question_type_id;
    copyForm.difficulty_level = form.difficulty_level;
    copyForm.points = form.points;
    copyForm.is_active = true;
    copyForm.alternatives = form.alternatives.map(alt => ({
        content: alt.content,
        is_correct: alt.is_correct,
        order: alt.order
    }));
    copyForm.tags = [...form.tags];
    copyForm.copy_from_id = props.question.id;

    copyForm.post(route('questions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCopy.value = true;
        },
        onFinish: () => {
            isCopying.value = false;
        },
    });
};
</script>

<style scoped>
@keyframes checkmark {
    0% {
        stroke-dashoffset: 50;
        opacity: 0;
        transform: scale(0.8);
    }
    50% {
        opacity: 1;
        transform: scale(1.1);
    }
    100% {
        stroke-dashoffset: 0;
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes bounce-right {
    0%, 100% {
        transform: translateX(0);
    }
    50% {
        transform: translateX(3px);
    }
}

.animate-checkmark {
    stroke-dasharray: 50;
    stroke-dashoffset: 50;
    animation: checkmark 0.6s ease-out forwards;
}

.animate-bounce-right {
    animation: bounce-right 0.6s ease-in-out infinite;
}
</style>