<template>
    <AppLayout>
        <Head :title="document.original_name" />

        <div class="space-y-6 animate-fade-in">
            <!-- Cabeçalho -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ document.original_name }}</h1>
                    <p class="mt-1 text-sm text-gray-600">Revise e edite as questões extraídas antes de importar</p>
                </div>
                <BaseButton :href="route('documents.index')" variant="outline">Voltar</BaseButton>
            </div>

            <!-- Status do Processamento -->
            <div v-if="document.status === 'pending' || document.status === 'processing'"
                 class="bg-white rounded-xl shadow-sm border border-gray-200 text-center py-12">
                <svg class="animate-spin h-12 w-12 mx-auto text-blue-600 mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Processando documento...</h3>
                <p class="text-gray-600 mb-1">Estamos extraindo as questões usando IA. Isso pode levar alguns minutos.</p>
                <p class="text-sm text-gray-400">Processando há {{ elapsedLabel }} — esta página se atualiza sozinha, não precisa recarregar.</p>
            </div>

            <!-- Erro no Processamento -->
            <div v-else-if="document.status === 'failed'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="text-center py-12">
                    <svg class="h-12 w-12 mx-auto text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Falha no processamento</h3>
                    <p class="text-gray-600 mb-4">{{ document.error_message }}</p>
                    <div class="flex items-center justify-center gap-3">
                        <BaseButton @click="reprocess">Tentar Novamente</BaseButton>
                        <BaseButton :href="route('documents.index')" variant="outline">Voltar</BaseButton>
                    </div>
                </div>
            </div>

            <!-- Questões Extraídas -->
            <div v-else-if="document.status === 'completed' && extractedQuestions.length > 0" class="space-y-6">
                <!-- Informações e Avisos -->
                <div v-if="metadata.warnings && metadata.warnings.length > 0"
                     class="bg-yellow-50 border border-yellow-200 rounded-xl shadow-sm p-6">
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

                <!-- Erro de Importação -->
                <div v-if="Object.keys(form.errors).length > 0"
                     class="bg-red-50 border border-red-200 rounded-xl shadow-sm p-6">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-red-800 mb-1">Não foi possível importar</h3>
                            <ul class="text-sm text-red-700 space-y-1">
                                <li v-for="(message, field) in form.errors" :key="field">• {{ message }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Aplicar disciplina/tópico a todas as selecionadas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Aplicar a todas as selecionadas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <FormField label="Matéria" v-slot="{ id }">
                            <select :id="id" v-model="bulkSubjectId" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option :value="null">Selecione...</option>
                                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                            </select>
                        </FormField>
                        <FormField label="Tópico" v-slot="{ id }">
                            <select :id="id" v-model="bulkTopicId" :disabled="!bulkSubjectId" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100">
                                <option :value="null">Selecione...</option>
                                <option v-for="topic in getTopicsForSubject(bulkSubjectId)" :key="topic.id" :value="topic.id">{{ topic.name }}</option>
                            </select>
                        </FormField>
                        <div class="flex items-end">
                            <BaseButton class="w-full" :disabled="selectedQuestions.length === 0 || !bulkSubjectId" @click="applyBulkSubjectTopic">
                                Aplicar às {{ selectedQuestions.length }} selecionada{{ selectedQuestions.length !== 1 ? 's' : '' }}
                            </BaseButton>
                        </div>
                    </div>
                </div>

                <!-- Barra de Ações -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="text-sm text-gray-600">
                        <span class="font-semibold text-gray-900">{{ selectedQuestions.length }}</span>
                        de
                        <span class="font-semibold text-gray-900">{{ extractedQuestions.length }}</span>
                        questões selecionadas
                    </div>
                    <div class="flex gap-3">
                        <BaseButton variant="outline" size="sm" @click="selectAll">
                            {{ selectedQuestions.length === extractedQuestions.length ? 'Desmarcar Todas' : 'Selecionar Todas' }}
                        </BaseButton>
                        <BaseButton size="sm" :loading="form.processing" :disabled="selectedQuestions.length === 0" @click="importQuestions">
                            {{ form.processing ? 'Importando...' : 'Importar Selecionadas' }}
                        </BaseButton>
                    </div>
                </div>

                <!-- Lista de Questões (paginada em lotes de 10) -->
                <div class="space-y-4">
                    <div v-for="{ question, index } in paginatedQuestions" :key="index"
                         class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                        <div class="flex gap-4">
                            <!-- Checkbox -->
                            <div class="flex-shrink-0 pt-1">
                                <input
                                    type="checkbox"
                                    :id="`question-${index}-select`"
                                    :checked="selectedQuestions.includes(index)"
                                    @change="toggleQuestion(index)"
                                    class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                                    :aria-label="`Selecionar questão ${question.number || index + 1}`"
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
                                <FormField label="Enunciado" required v-slot="{ id }">
                                    <textarea
                                        :id="id"
                                        v-model="question.statement"
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-300': !question.statement || question.statement.length < 10 }"
                                    ></textarea>
                                    <p v-if="!question.statement || question.statement.length < 10" class="text-xs text-red-600 mt-1">
                                        Enunciado deve ter pelo menos 10 caracteres
                                    </p>
                                </FormField>

                                <!-- Tipo, Dificuldade e Pontos -->
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <FormField label="Tipo" required v-slot="{ id }">
                                        <select :id="id" v-model="question.type" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="multiple_choice">Múltipla Escolha</option>
                                            <option value="true_false">Verdadeiro/Falso</option>
                                            <option value="essay">Dissertativa</option>
                                        </select>
                                    </FormField>
                                    <FormField label="Dificuldade" required v-slot="{ id }">
                                        <select :id="id" v-model="question.difficulty_hint" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="easy">Fácil</option>
                                            <option value="medium">Médio</option>
                                            <option value="hard">Difícil</option>
                                        </select>
                                    </FormField>
                                    <FormField label="Pontos" v-slot="{ id }">
                                        <input
                                            :id="id"
                                            v-model.number="question.points"
                                            type="number"
                                            step="0.5"
                                            min="0.5"
                                            max="10"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </FormField>
                                    <FormField label="Matéria" required v-slot="{ id }">
                                        <select
                                            :id="id"
                                            v-model="question.subject_id"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            :class="{ 'border-red-400': selectedQuestions.includes(index) && !question.subject_id && attemptedImport }"
                                        >
                                            <option :value="null">Selecione...</option>
                                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                                {{ subject.name }}
                                            </option>
                                        </select>
                                    </FormField>
                                </div>

                                <!-- Tópico -->
                                <FormField v-if="question.subject_id" label="Tópico" v-slot="{ id }">
                                    <select :id="id" v-model="question.topic_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option :value="null">Selecione...</option>
                                        <option
                                            v-for="topic in getTopicsForSubject(question.subject_id)"
                                            :key="topic.id"
                                            :value="topic.id"
                                        >
                                            {{ topic.name }}
                                        </option>
                                    </select>
                                </FormField>

                                <!-- Alternativas -->
                                <div v-if="question.type === 'multiple_choice' && question.alternatives && question.alternatives.length > 0"
                                     class="space-y-2">
                                    <span class="block text-sm font-medium text-gray-700 mb-2">Alternativas</span>
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
                                                :aria-label="`Marcar alternativa ${alt.letter} como correta`"
                                            />
                                        </div>
                                        <textarea
                                            v-model="alt.content"
                                            rows="2"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            :class="{ 'bg-green-50 border-green-300': alt.is_correct }"
                                        ></textarea>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">✓ = Alternativa correta</p>
                                </div>

                                <!-- Explicação -->
                                <FormField v-if="question.explanation" label="Explicação (opcional)" v-slot="{ id }">
                                    <textarea :id="id" v-model="question.explanation" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </FormField>
                            </div>
                        </div>
                    </div>
                </div>

                <nav v-if="totalPages > 1" aria-label="Paginação da revisão" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">{{ paginationSummary }}</p>
                    <ul class="flex flex-wrap items-center gap-1">
                        <li>
                            <button type="button" :disabled="currentPage === 1" @click="goToBatch(currentPage - 1)"
                                    aria-label="Lote anterior"
                                    class="inline-flex min-w-[2.25rem] items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                &laquo; Anterior
                            </button>
                        </li>
                        <li v-for="page in totalPages" :key="page">
                            <button type="button" @click="goToBatch(page)"
                                    :aria-current="page === currentPage ? 'page' : undefined"
                                    :aria-label="`Lote ${page}`"
                                    :class="[
                                        'inline-flex min-w-[2.25rem] items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                        page === currentPage
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
                                    ]">
                                {{ page }}
                            </button>
                        </li>
                        <li>
                            <button type="button" :disabled="currentPage === totalPages" @click="goToBatch(currentPage + 1)"
                                    aria-label="Próximo lote"
                                    class="inline-flex min-w-[2.25rem] items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                Próxima &raquo;
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Nenhuma questão extraída -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 text-center py-12">
                <svg class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma questão encontrada</h3>
                <p class="text-gray-600 mb-4">O documento não contém questões que pudemos extrair.</p>
                <BaseButton :href="route('documents.index')" variant="outline">Voltar</BaseButton>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import BaseButton from '@/Components/UI/BaseButton.vue';
import FormField from '@/Components/UI/FormField.vue';

const props = defineProps({
    document: Object,
    subjects: Array,
    topics: Array,
    questionTypes: Array,
});

// `extractedQuestions`/`metadata` só são derivados de `props.document` na
// primeira vez que ele chega "completed" — depois disso o usuário edita os
// campos localmente (v-model), então não podemos simplesmente recalcular a
// partir da prop a cada poll, ou perderíamos as edições em andamento.
const extractedQuestions = reactive([]);
const metadata = ref({});
const selectedQuestions = ref([]);
const attemptedImport = ref(false);
const bulkSubjectId = ref(null);
const bulkTopicId = ref(null);
const currentPage = ref(1);
const perBatch = 10;
const startedAt = ref(Date.now());
const elapsedSeconds = ref(0);

const hydrateFromDocument = () => {
    if (props.document.status !== 'completed' || extractedQuestions.length > 0) return;

    const questions = props.document.extraction_result?.questions || [];
    extractedQuestions.push(...questions);
    metadata.value = props.document.extraction_result?.metadata || {};
    selectedQuestions.value = questions.map((_, i) => i);
};

hydrateFromDocument();

const form = useForm({
    questions: [],
});

const paginatedQuestions = computed(() => {
    const start = (currentPage.value - 1) * perBatch;
    return extractedQuestions
        .map((question, index) => ({ question, index }))
        .slice(start, start + perBatch);
});

const totalPages = computed(() => Math.max(1, Math.ceil(extractedQuestions.length / perBatch)));

// Paginação 100% local (não é Inertia/servidor): os lotes só reorganizam
// quais questões já extraídas aparecem na tela, sem navegar — trocar de
// lote não pode descartar as edições feitas nos outros lotes.
const goToBatch = (page) => {
    currentPage.value = Math.min(Math.max(page, 1), totalPages.value);
};

const paginationSummary = computed(() => `Lote ${currentPage.value} de ${totalPages.value} (${extractedQuestions.length} questões no total)`);

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
    if (selectedQuestions.value.length === extractedQuestions.length) {
        selectedQuestions.value = [];
    } else {
        selectedQuestions.value = extractedQuestions.map((_, i) => i);
    }
};

const setCorrectAlternative = (questionIndex, altIndex) => {
    extractedQuestions[questionIndex].alternatives.forEach((alt, i) => {
        alt.is_correct = i === altIndex;
    });
};

const applyBulkSubjectTopic = () => {
    selectedQuestions.value.forEach((index) => {
        extractedQuestions[index].subject_id = bulkSubjectId.value;
        extractedQuestions[index].topic_id = bulkTopicId.value;
    });
};

const importQuestions = () => {
    attemptedImport.value = true;

    const missingSubject = selectedQuestions.value.some(
        index => !extractedQuestions[index].subject_id
    );

    if (missingSubject) {
        form.setError('questions', 'Selecione uma disciplina para cada questão selecionada antes de importar.');
        return;
    }

    form.clearErrors();

    const questionsToImport = selectedQuestions.value.map(index => {
        const question = extractedQuestions[index];
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

const reprocess = () => {
    router.post(route('documents.reprocess', props.document.id));
};

// Polling automático enquanto o documento está sendo processado — antes,
// esta era a única tela do fluxo de documentos que exigia recarregar na
// mão para ver o resultado (a listagem já fazia polling havia tempo).
const pollInterval = ref(null);
let elapsedTimer = null;

const startPolling = () => {
    if (props.document.status === 'pending' || props.document.status === 'processing') {
        pollInterval.value = setInterval(() => {
            router.reload({ only: ['document'], preserveScroll: true });
        }, 5000);

        elapsedTimer = setInterval(() => {
            elapsedSeconds.value = Math.floor((Date.now() - startedAt.value) / 1000);
        }, 1000);
    }
};

const elapsedLabel = computed(() => {
    if (elapsedSeconds.value < 60) return `${elapsedSeconds.value}s`;
    const minutes = Math.floor(elapsedSeconds.value / 60);
    const seconds = elapsedSeconds.value % 60;
    return `${minutes}min ${seconds}s`;
});

watch(() => props.document.status, (status) => {
    if (status === 'completed') {
        hydrateFromDocument();
    }

    if (status !== 'pending' && status !== 'processing') {
        if (pollInterval.value) clearInterval(pollInterval.value);
        if (elapsedTimer) clearInterval(elapsedTimer);
    }
});

onMounted(startPolling);

onUnmounted(() => {
    if (pollInterval.value) clearInterval(pollInterval.value);
    if (elapsedTimer) clearInterval(elapsedTimer);
});
</script>
