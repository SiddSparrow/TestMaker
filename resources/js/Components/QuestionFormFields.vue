<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Informações Básicas</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Matéria -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label :for="ids.subject" class="block text-sm font-medium text-gray-700">
                        Matéria <span class="text-red-500">*</span>
                    </label>
                    <button type="button" class="text-xs font-medium text-blue-600 hover:text-blue-800"
                            @click="showNewSubjectForm = !showNewSubjectForm">
                        {{ showNewSubjectForm ? 'Cancelar' : '+ Nova matéria' }}
                    </button>
                </div>

                <div v-if="showNewSubjectForm" class="flex gap-2 mb-2">
                    <label :for="ids.newSubject" class="sr-only">Nome da nova matéria</label>
                    <input :id="ids.newSubject" v-model="newSubjectName" type="text"
                           placeholder="Nome da nova matéria"
                           @keydown.enter.prevent="createSubject"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <button type="button"
                            :disabled="!newSubjectName || creatingSubject"
                            @click="createSubject"
                            class="px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Criar
                    </button>
                </div>
                <p v-if="newSubjectError" class="text-xs text-red-600 mb-2">{{ newSubjectError }}</p>

                <select :id="ids.subject" v-model="localSubjectId"
                        @change="handleSubjectChange"
                        autofocus
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        :class="{ 'border-red-500': errors.subject_id }">
                    <option value="">Selecione uma matéria</option>
                    <option v-for="subject in localSubjects"
                            :key="subject.id"
                            :value="subject.id"
                            :style="{ color: getReadableTextColor(subject.color, { light: subject.color, dark: '#1f2937' }) }">
                        {{ subject.name }}
                    </option>
                </select>
                <p v-if="errors.subject_id" class="mt-1 text-sm text-red-600">
                    {{ errors.subject_id }}
                </p>
            </div>

            <!-- Tópico -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label :for="ids.topic" class="block text-sm font-medium text-gray-700">
                        Tópico
                    </label>
                    <button v-if="localSubjectId" type="button" class="text-xs font-medium text-blue-600 hover:text-blue-800"
                            @click="showNewTopicForm = !showNewTopicForm">
                        {{ showNewTopicForm ? 'Cancelar' : '+ Novo tópico' }}
                    </button>
                </div>

                <div v-if="showNewTopicForm && localSubjectId" class="flex gap-2 mb-2">
                    <label :for="ids.newTopic" class="sr-only">Nome do novo tópico</label>
                    <input :id="ids.newTopic" v-model="newTopicName" type="text"
                           placeholder="Nome do novo tópico"
                           @keydown.enter.prevent="createTopic"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <button type="button"
                            :disabled="!newTopicName || creatingTopic"
                            @click="createTopic"
                            class="px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Criar
                    </button>
                </div>
                <p v-if="newTopicError" class="text-xs text-red-600 mb-2">{{ newTopicError }}</p>

                <select :id="ids.topic" v-model="localTopicId"
                        @change="handleTopicChange"
                        :disabled="!localSubjectId || filteredTopics.length === 0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed transition-colors"
                        :class="{ 'border-red-500': errors.topic_id }">
                    <option value="">Selecione um tópico (opcional)</option>
                    <option v-for="topic in filteredTopics"
                            :key="topic.id"
                            :value="topic.id">
                        {{ topic.name }}
                    </option>
                </select>
                <p v-if="errors.topic_id" class="mt-1 text-sm text-red-600">
                    {{ errors.topic_id }}
                </p>
                <p v-if="localSubjectId && filteredTopics.length === 0"
                   class="mt-1 text-xs text-gray-500">
                    Nenhum tópico disponível para esta matéria
                </p>
            </div>

            <!-- Tipo de Questão -->
            <div>
                <label :for="ids.questionType" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo de Questão <span class="text-red-500">*</span>
                </label>
                <select :id="ids.questionType" v-model="localQuestionTypeId"
                        @change="handleQuestionTypeChange"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        :class="{ 'border-red-500': errors.question_type_id }">
                    <option value="">Selecione o tipo</option>
                    <option v-for="type in questionTypes"
                            :key="type.id"
                            :value="type.id">
                        {{ type.name }}
                    </option>
                </select>
                <p v-if="errors.question_type_id" class="mt-1 text-sm text-red-600">
                    {{ errors.question_type_id }}
                </p>
                <p v-if="selectedQuestionType" class="mt-1 text-xs text-gray-500">
                    {{ getQuestionTypeDescription(selectedQuestionType.slug) }}
                </p>
            </div>

            <!-- Dificuldade -->
            <div>
                <span :id="ids.difficulty" class="block text-sm font-medium text-gray-700 mb-2">
                    Dificuldade <span class="text-red-500">*</span>
                </span>
                <div class="flex space-x-2" role="radiogroup" :aria-labelledby="ids.difficulty">
                    <button type="button"
                            role="radio"
                            :aria-checked="localDifficulty === 'easy'"
                            @click="setDifficulty('easy')"
                            class="flex-1 px-4 py-2 rounded-md border transition-all duration-200 font-medium"
                            :class="localDifficulty === 'easy'
                                ? 'bg-green-100 border-green-500 text-green-700 shadow-sm'
                                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-gray-400'">
                        <span class="flex items-center justify-center gap-1">
                            <svg v-if="localDifficulty === 'easy'" class="w-4 h-4 difficulty-check-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Fácil
                        </span>
                    </button>
                    <button type="button"
                            role="radio"
                            :aria-checked="localDifficulty === 'medium'"
                            @click="setDifficulty('medium')"
                            class="flex-1 px-4 py-2 rounded-md border transition-all duration-200 font-medium"
                            :class="localDifficulty === 'medium'
                                ? 'bg-yellow-100 border-yellow-500 text-yellow-700 shadow-sm'
                                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-gray-400'">
                        <span class="flex items-center justify-center gap-1">
                            <svg v-if="localDifficulty === 'medium'" class="w-4 h-4 difficulty-check-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Médio
                        </span>
                    </button>
                    <button type="button"
                            role="radio"
                            :aria-checked="localDifficulty === 'hard'"
                            @click="setDifficulty('hard')"
                            class="flex-1 px-4 py-2 rounded-md border transition-all duration-200 font-medium"
                            :class="localDifficulty === 'hard'
                                ? 'bg-red-100 border-red-500 text-red-700 shadow-sm'
                                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-gray-400'">
                        <span class="flex items-center justify-center gap-1">
                            <svg v-if="localDifficulty === 'hard'" class="w-4 h-4 difficulty-check-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Difícil
                        </span>
                    </button>
                </div>
                <p v-if="errors.difficulty_level" class="mt-1 text-sm text-red-600">
                    {{ errors.difficulty_level }}
                </p>
            </div>
        </div>

        <!-- Pontuação e Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label :for="ids.points" class="block text-sm font-medium text-gray-700 mb-2">
                    Pontos <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-3">
                    <input type="number"
                           :id="ids.points"
                           v-model.number="localPoints"
                           @input="handlePointsChange"
                           min="0.5"
                           max="10"
                           step="0.5"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors"
                           :class="{ 'border-red-500': errors.points }">
                    <span class="text-sm text-gray-500 whitespace-nowrap">
                        {{ localPoints === 1 ? 'ponto' : 'pontos' }}
                    </span>
                </div>
                <p v-if="errors.points" class="mt-1 text-sm text-red-600">
                    {{ errors.points }}
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Valor mínimo: 0,5 | Valor máximo: 10
                </p>
            </div>

            <div class="flex items-center">
                <label :for="ids.isActive" class="flex items-center space-x-2 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox"
                               :id="ids.isActive"
                               v-model="localIsActive"
                               @change="handleIsActiveChange"
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition-colors">
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">
                            Questão ativa
                        </span>
                        <p class="text-xs text-gray-500">
                            {{ localIsActive ? 'Visível para uso em provas' : 'Oculta, não aparecerá em buscas' }}
                        </p>
                    </div>
                </label>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, useId } from 'vue';
import { getReadableTextColor } from '@/utils/color';

const uid = useId();
const ids = {
    subject: `subject-${uid}`,
    topic: `topic-${uid}`,
    questionType: `question-type-${uid}`,
    points: `points-${uid}`,
    isActive: `is-active-${uid}`,
    difficulty: `difficulty-${uid}`,
    newSubject: `new-subject-${uid}`,
    newTopic: `new-topic-${uid}`,
};

const props = defineProps({
    subjectId: {
        type: [Number, String],
        default: ''
    },
    topicId: {
        type: [Number, String],
        default: ''
    },
    questionTypeId: {
        type: [Number, String],
        default: ''
    },
    difficultyLevel: {
        type: String,
        default: 'medium'
    },
    points: {
        type: Number,
        default: 1
    },
    isActive: {
        type: Boolean,
        default: true
    },
    subjects: {
        type: Array,
        required: true
    },
    topics: {
        type: Array,
        required: true
    },
    questionTypes: {
        type: Array,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits([
    'update:subjectId',
    'update:topicId',
    'update:questionTypeId',
    'update:difficultyLevel',
    'update:points',
    'update:isActive',
    'subject-change',
    'question-type-change'
]);

// Estados locais
const localSubjectId = ref(props.subjectId);
const localTopicId = ref(props.topicId);
const localQuestionTypeId = ref(props.questionTypeId);
const localDifficulty = ref(props.difficultyLevel);
const localPoints = ref(props.points);
const localIsActive = ref(props.isActive);

// Cópias locais de subjects/topics — props não podem ser mutadas, e uma
// matéria/tópico criado inline (ver createSubject/createTopic) precisa
// aparecer no <select> imediatamente, sem esperar um reload da página.
const localSubjects = ref([...props.subjects]);
const localTopics = ref([...props.topics]);

// Criação inline de matéria/tópico — sem isto, uma questão sem nenhuma
// matéria cadastrada não tinha como ser criada a não ser abandonando o
// formulário para ir cadastrar a matéria em outro lugar.
const showNewSubjectForm = ref(false);
const newSubjectName = ref('');
const newSubjectError = ref('');
const creatingSubject = ref(false);

const showNewTopicForm = ref(false);
const newTopicName = ref('');
const newTopicError = ref('');
const creatingTopic = ref(false);

// Watch para sincronizar props com estado local
watch(() => props.subjectId, (val) => localSubjectId.value = val);
watch(() => props.topicId, (val) => localTopicId.value = val);
watch(() => props.questionTypeId, (val) => localQuestionTypeId.value = val);
watch(() => props.difficultyLevel, (val) => localDifficulty.value = val);
watch(() => props.points, (val) => localPoints.value = val);
watch(() => props.isActive, (val) => localIsActive.value = val);

// Computed: tópicos filtrados pela matéria
const filteredTopics = computed(() => {
    if (!localSubjectId.value) return [];
    return localTopics.value.filter(topic => topic.subject_id == localSubjectId.value);
});

// Computed: tipo de questão selecionado
const selectedQuestionType = computed(() => {
    return props.questionTypes.find(t => t.id == localQuestionTypeId.value);
});

// Handlers
const handleSubjectChange = () => {
    emit('update:subjectId', localSubjectId.value);
    
    // Limpa o tópico se não existir na nova matéria
    if (!filteredTopics.value.find(t => t.id == localTopicId.value)) {
        localTopicId.value = '';
        emit('update:topicId', '');
    }
    
    emit('subject-change', localSubjectId.value);
};

const handleTopicChange = () => {
    emit('update:topicId', localTopicId.value);
};

const createSubject = async () => {
    if (!newSubjectName.value || creatingSubject.value) return;

    creatingSubject.value = true;
    newSubjectError.value = '';

    try {
        const response = await window.axios.post(route('subjects.store'), {
            name: newSubjectName.value,
        });

        const subject = response.data.subject;
        localSubjects.value.push(subject);
        localSubjectId.value = subject.id;
        handleSubjectChange();

        newSubjectName.value = '';
        showNewSubjectForm.value = false;
    } catch (error) {
        newSubjectError.value = error.response?.data?.errors?.name?.[0] || 'Não foi possível criar a matéria.';
    } finally {
        creatingSubject.value = false;
    }
};

const createTopic = async () => {
    if (!newTopicName.value || creatingTopic.value || !localSubjectId.value) return;

    creatingTopic.value = true;
    newTopicError.value = '';

    try {
        const response = await window.axios.post(route('topics.store'), {
            subject_id: localSubjectId.value,
            name: newTopicName.value,
        });

        const topic = response.data.topic;
        localTopics.value.push(topic);
        localTopicId.value = topic.id;
        handleTopicChange();

        newTopicName.value = '';
        showNewTopicForm.value = false;
    } catch (error) {
        newTopicError.value = error.response?.data?.errors?.name?.[0] || 'Não foi possível criar o tópico.';
    } finally {
        creatingTopic.value = false;
    }
};

const handleQuestionTypeChange = () => {
    emit('update:questionTypeId', localQuestionTypeId.value);
    emit('question-type-change', selectedQuestionType.value);
};

const setDifficulty = (level) => {
    localDifficulty.value = level;
    emit('update:difficultyLevel', level);
};

const handlePointsChange = () => {
    // Garante que está entre 0.5 e 10
    if (localPoints.value < 0.5) localPoints.value = 0.5;
    if (localPoints.value > 10) localPoints.value = 10;
    
    emit('update:points', localPoints.value);
};

const handleIsActiveChange = () => {
    emit('update:isActive', localIsActive.value);
};

// Helper: descrição do tipo de questão
const getQuestionTypeDescription = (slug) => {
    const descriptions = {
        'multipla-escolha': 'Apenas uma alternativa correta',
        'verdadeiro-falso': 'Marque as afirmações verdadeiras',
        'multipla-resposta': 'Pode ter mais de uma alternativa correta',
        'dissertativa': 'Resposta escrita por extenso'
    };
    return descriptions[slug] || '';
};

// Validação exposta
defineExpose({
    validate: () => {
        return {
            isValid: !!(localSubjectId.value && localQuestionTypeId.value && localDifficulty.value),
            errors: {
                subject: !localSubjectId.value ? 'Selecione uma matéria' : null,
                questionType: !localQuestionTypeId.value ? 'Selecione o tipo de questão' : null,
            }
        };
    }
});
</script>

<style scoped>
/* Transições suaves */
select, input, button {
    transition: all 0.2s ease;
}

/* Animação de check nos botões de dificuldade */
.difficulty-check-icon {
    animation: checkmark-pop 0.3s ease;
}

@keyframes checkmark-pop {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}
</style>