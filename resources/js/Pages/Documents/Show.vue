<template>
    <AppLayout>
        <div class="space-y-6 fade-in">
            <!-- Cabeçalho -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="slide-up" style="animation-delay: 100ms">
                    <h1 class="page-title-elegant">{{ document.original_name }}</h1>
                    <p class="page-subtitle-elegant">Revise e edite as questões extraídas antes de importar</p>
                </div>
                <div class="slide-up" style="animation-delay: 200ms">
                    <a :href="route('documents.index')" class="btn-elegant btn-elegant-outline">
                        Voltar
                    </a>
                </div>
            </div>

            <!-- Status do Processamento -->
            <div v-if="document.status === 'pending' || document.status === 'processing'"
                 class="card-elegant slide-up text-center py-12">
                <svg class="animate-spin h-12 w-12 mx-auto text-blue-600 mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Processando documento...</h3>
                <p class="text-gray-600 mb-4">Estamos extraindo as questões usando IA. Isso pode levar alguns minutos.</p>
                <button @click="refreshPage" class="btn-elegant btn-elegant-outline">
                    Atualizar Página
                </button>
            </div>

            <!-- Erro no Processamento -->
            <div v-else-if="document.status === 'failed'" class="card-elegant slide-up">
                <div class="text-center py-12">
                    <svg class="h-12 w-12 mx-auto text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Falha no processamento</h3>
                    <p class="text-gray-600 mb-4">{{ document.error_message }}</p>
                    <div class="space-x-3">
                        <button @click="reprocess" class="btn-elegant btn-elegant-primary">
                            Tentar Novamente
                        </button>
                        <a :href="route('documents.index')" class="btn-elegant btn-elegant-outline">
                            Voltar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Questões Extraídas -->
            <div v-else-if="document.status === 'completed' && extractedQuestions.length > 0">
                <!-- Informações e Avisos -->
                <div v-if="metadata.warnings && metadata.warnings.length > 0"
                     class="card-elegant slide-up bg-yellow-50 border border-yellow-200">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-yellow-800 mb-1">Avisos da Extração</h3>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li v-for="(warning, index) in metadata.warnings" :key="index">• {{ warning }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Barra de Ações -->
                <div class="card-elegant slide-up flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <span class="font-semibold text-gray-900">{{ selectedQuestions.length }}</span>
                        de
                        <span class="font-semibold text-gray-900">{{ extractedQuestions.length }}</span>
                        questões selecionadas
                    </div>
                    <div class="flex gap-3">
                        <button @click="selectAll" class="btn-elegant btn-elegant-outline btn-elegant-sm">
                            {{ selectedQuestions.length === extractedQuestions.length ? 'Desmarcar Todas' : 'Selecionar Todas' }}
                        </button>
                        <button
                            @click="importQuestions"
                            :disabled="selectedQuestions.length === 0 || form.processing"
                            class="btn-elegant btn-elegant-primary btn-elegant-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Importando...' : 'Importar Selecionadas' }}
                        </button>
                    </div>
                </div>

                <!-- Lista de Questões -->
                <div class="space-y-4">
                    <div v-for="(question, index) in extractedQuestions" :key="index"
                         class="card-elegant slide-up hover:shadow-lg transition-shadow">
                        <div class="flex gap-4">
                            <!-- Checkbox -->
                            <div class="flex-shrink-0 pt-1">
                                <input
                                    type="checkbox"
                                    :checked="selectedQuestions.includes(index)"
                                    @change="toggleQuestion(index)"
                                    class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                                />
                            </div>

                            <!-- Conteúdo -->
                            <div class="flex-1 space-y-4">
                                <!-- Cabeçalho da Questão -->
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="text-lg font-bold text-gray-900">Questão {{ question.number || index + 1 }}</span>
                                        <span v-if="question.confidence !== undefined"
                                              :class="getConfidenceClass(question.confidence)"
                                              class="px-2 py-1 text-xs font-semibold rounded-full">
                                            Confiança: {{ Math.round(question.confidence * 100) }}%
                                        </span>
                                    </div>
                                </div>

                                <!-- Enunciado -->
                                <div class="form-group-elegant">
                                    <label class="form-label-elegant">Enunciado *</label>
                                    <textarea
                                        v-model="question.statement"
                                        rows="3"
                                        class="form-control-elegant"
                                        :class="{ 'border-red-300': !question.statement || question.statement.length < 10 }"
                                    ></textarea>
                                    <p v-if="!question.statement || question.statement.length < 10" class="text-xs text-red-600 mt-1">
                                        Enunciado deve ter pelo menos 10 caracteres
                                    </p>
                                </div>

                                <!-- Tipo, Dificuldade e Pontos -->
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="form-group-elegant">
                                        <label class="form-label-elegant">Tipo *</label>
                                        <select v-model="question.type" class="form-control-elegant">
                                            <option value="multiple_choice">Múltipla Escolha</option>
                                            <option value="true_false">Verdadeiro/Falso</option>
                                            <option value="essay">Dissertativa</option>
                                        </select>
                                    </div>
                                    <div class="form-group-elegant">
                                        <label class="form-label-elegant">Dificuldade *</label>
                                        <select v-model="question.difficulty_hint" class="form-control-elegant">
                                            <option value="easy">Fácil</option>
                                            <option value="medium">Médio</option>
                                            <option value="hard">Difícil</option>
                                        </select>
                                    </div>
                                    <div class="form-group-elegant">
                                        <label class="form-label-elegant">Pontos</label>
                                        <input
                                            v-model.number="question.points"
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            class="form-control-elegant"
                                        />
                                    </div>
                                    <div class="form-group-elegant">
                                        <label class="form-label-elegant">Disciplina</label>
                                        <select v-model="question.subject_id" class="form-control-elegant">
                                            <option :value="null">Selecione...</option>
                                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                                {{ subject.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Tópico -->
                                <div v-if="question.subject_id" class="form-group-elegant">
                                    <label class="form-label-elegant">Tópico</label>
                                    <select v-model="question.topic_id" class="form-control-elegant">
                                        <option :value="null">Selecione...</option>
                                        <option
                                            v-for="topic in getTopicsForSubject(question.subject_id)"
                                            :key="topic.id"
                                            :value="topic.id"
                                        >
                                            {{ topic.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Alternativas -->
                                <div v-if="question.type === 'multiple_choice' && question.alternatives && question.alternatives.length > 0"
                                     class="space-y-2">
                                    <label class="form-label-elegant">Alternativas</label>
                                    <div v-for="(alt, altIndex) in question.alternatives" :key="altIndex"
                                         class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-2 flex-shrink-0 pt-2">
                                            <span class="font-semibold text-gray-700">{{ alt.letter }})</span>
                                            <input
                                                type="radio"
                                                :name="`correct-${index}`"
                                                :checked="alt.is_correct"
                                                @change="setCorrectAlternative(index, altIndex)"
                                                class="h-4 w-4 text-green-600"
                                                title="Marcar como correta"
                                            />
                                        </div>
                                        <textarea
                                            v-model="alt.content"
                                            rows="2"
                                            class="flex-1 form-control-elegant"
                                            :class="{ 'bg-green-50 border-green-300': alt.is_correct }"
                                        ></textarea>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        ✓ = Alternativa correta
                                    </p>
                                </div>

                                <!-- Explicação -->
                                <div v-if="question.explanation" class="form-group-elegant">
                                    <label class="form-label-elegant">Explicação (opcional)</label>
                                    <textarea
                                        v-model="question.explanation"
                                        rows="2"
                                        class="form-control-elegant"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nenhuma questão extraída -->
            <div v-else class="card-elegant slide-up text-center py-12">
                <svg class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma questão encontrada</h3>
                <p class="text-gray-600 mb-4">O documento não contém questões que pudemos extrair.</p>
                <a :href="route('documents.index')" class="btn-elegant btn-elegant-outline">
                    Voltar
                </a>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    document: Object,
    subjects: Array,
    topics: Array,
    questionTypes: Array,
});

const extractedQuestions = ref(props.document.extraction_result?.questions || []);
const metadata = ref(props.document.extraction_result?.metadata || {});
const selectedQuestions = ref(extractedQuestions.value.map((_, i) => i));

const form = useForm({
    questions: [],
});

const getConfidenceClass = (confidence) => {
    if (confidence >= 0.9) return 'bg-green-100 text-green-800';
    if (confidence >= 0.7) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
};

const getTopicsForSubject = (subjectId) => {
    return props.topics.filter(topic => topic.subject_id === subjectId);
};

const toggleQuestion = (index) => {
    const idx = selectedQuestions.value.indexOf(index);
    if (idx > -1) {
        selectedQuestions.value.splice(idx, 1);
    } else {
        selectedQuestions.value.push(index);
    }
};

const selectAll = () => {
    if (selectedQuestions.value.length === extractedQuestions.value.length) {
        selectedQuestions.value = [];
    } else {
        selectedQuestions.value = extractedQuestions.value.map((_, i) => i);
    }
};

const setCorrectAlternative = (questionIndex, altIndex) => {
    extractedQuestions.value[questionIndex].alternatives.forEach((alt, i) => {
        alt.is_correct = i === altIndex;
    });
};

const importQuestions = () => {
    const questionsToImport = selectedQuestions.value.map(index => {
        const question = extractedQuestions.value[index];
        return {
            statement: question.statement,
            type: question.type,
            subject_id: question.subject_id || null,
            topic_id: question.topic_id || null,
            difficulty_level: question.difficulty_hint || 'medium',
            points: question.points || 1.0,
            explanation: question.explanation || null,
            alternatives: question.alternatives || [],
        };
    });

    form.questions = questionsToImport;
    form.post(route('documents.import-questions', props.document.id));
};

const refreshPage = () => {
    router.reload();
};

const reprocess = () => {
    router.post(route('documents.reprocess', props.document.id));
};
</script>
