<template>
    <Modal :show="show" max-width="5xl" @close="closePreview">
        <div class="relative max-h-[90vh] flex flex-col">
            <!-- Header -->
                        <div class="no-print flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Preview da Prova</h3>
                                    <p class="text-xs text-gray-600">Visualização antes de exportar</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- Zoom Controls -->
                                <div class="flex items-center gap-1 mr-2">
                                    <button @click="zoomOut"
                                        aria-label="Diminuir zoom"
                                        class="p-2 text-gray-600 hover:bg-gray-100 rounded transition-colors"
                                        :disabled="zoom <= 30">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                                        </svg>
                                    </button>
                                    <span class="text-sm text-gray-600 w-16 text-center">{{ zoom }}%</span>
                                    <button @click="zoomIn"
                                        aria-label="Aumentar zoom"
                                        class="p-2 text-gray-600 hover:bg-gray-100 rounded transition-colors"
                                        :disabled="zoom >= 150">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Toggle Gabarito -->
                                <button @click="showAnswers = !showAnswers"
                                    class="px-3 py-2 text-sm rounded-md transition-colors flex items-center gap-1.5" :class="showAnswers
                                        ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                                    <svg v-if="showAnswers" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ showAnswers ? 'Com Gabarito' : 'Sem Gabarito' }}
                                </button>

                                <!-- Close Button -->
                                <button @click="closePreview"
                                    aria-label="Fechar"
                                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Preview Content (Scrollable) -->
                        <div ref="previewContainer" class="flex-1 overflow-auto p-6 bg-gray-100">
                            <div ref="previewContent"
                                class="mx-auto bg-white shadow-lg transition-transform duration-200"
                                :class="getPageOrientationClass()" :style="getPageStyle()">

                                <!-- A4 Page Content -->
                                <div :style="getContentStyle()">
                                    <!-- Header -->
                                    <div class="text-center mb-8 pb-6 border-b-2 border-gray-300">
                                        <div class="flex items-start gap-4">
                                            <!-- Logo (se habilitado) -->
                                            <div v-if="exam.header_config?.show_logo"
                                                class="flex-shrink-0 w-20 h-20 bg-gray-200 rounded border-2 border-dashed border-gray-400 flex items-center justify-center text-xs text-gray-500">
                                                LOGO
                                            </div>

                                            <!-- School/Exam Info -->
                                            <div class="flex-1">
                                                <h1 v-if="exam.header_config?.school_name"
                                                    class="text-2xl font-bold text-gray-900 mb-2">
                                                    {{ exam.header_config.school_name }}
                                                </h1>

                                                <h2 class="text-xl font-semibold text-gray-800 mb-1">
                                                    {{ exam.title }}
                                                </h2>

                                                <p v-if="exam.description" class="text-sm text-gray-600 mt-2">
                                                    {{ exam.description }}
                                                </p>

                                                <div v-if="exam.header_config?.show_date && exam.exam_date"
                                                    class="text-sm text-gray-600 mt-2">
                                                    Data: {{ formatDate(exam.exam_date) }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Student Info Fields -->
                                        <div v-if="exam.header_config?.show_student_info"
                                            class="mt-4 text-left space-y-2 text-sm text-gray-700">
                                            <div class="flex gap-4">
                                                <div class="flex-1">
                                                    <span class="font-medium">Nome:</span>
                                                    <span class="ml-2 border-b border-gray-400 inline-block w-96">
                                                        &nbsp;
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex gap-4">
                                                <div>
                                                    <span class="font-medium">Turma:</span>
                                                    <span class="ml-2 border-b border-gray-400 inline-block w-32">
                                                        &nbsp;
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="font-medium">Data:</span>
                                                    <span class="ml-2 border-b border-gray-400 inline-block w-32">
                                                        &nbsp;
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="font-medium">Nota:</span>
                                                    <span class="ml-2 border-b border-gray-400 inline-block w-20">
                                                        &nbsp;
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Total Points -->
                                        <div class="mt-4 text-sm font-medium text-gray-700">
                                            Valor Total: {{ totalPoints }} pontos
                                        </div>
                                    </div>

                                    <!-- Questions -->
                                    <div class="space-y-6" :class="{
                                        'exam-columns-2': exam.format_config?.columns === 2,
                                        'text-justify': exam.format_config?.justify_text
                                    }">
                                        <div v-for="(question, index) in displayQuestions" :key="question.id"
                                            class="question-block break-inside-avoid">

                                            <!-- Question Header -->
                                            <div class="flex items-start gap-3 mb-3">
                                                <div
                                                    class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm">
                                                    {{ index + 1 }}
                                                </div>

                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-xs text-gray-500 uppercase tracking-wide">
                                                            {{ getQuestionTypeName(question.question_type_id) }}
                                                        </span>
                                                        <span v-if="exam.format_config?.show_question_points !== false"
                                                            class="text-xs font-semibold text-gray-700">
                                                            ({{ getQuestionPoints(question) }} {{
                                                            getQuestionPoints(question) === 1 ? 'ponto' : 'pontos' }})
                                                        </span>
                                                    </div>

                                                    <!-- Statement -->
                                                    <div
                                                        class="text-base text-gray-900 leading-relaxed whitespace-pre-wrap">
                                                        {{ question.statement }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Alternatives (if applicable) -->
                                            <div v-if="question.alternatives && question.alternatives.length > 0"
                                                class="ml-11 mt-3 space-y-2">
                                                <div v-for="(alt, altIndex) in getAlternatives(question)" :key="alt.id"
                                                    class="flex items-start gap-3 py-1">
                                                    <div class="flex-shrink-0 w-6 h-6 rounded border-2 border-gray-400 flex items-center justify-center text-xs font-semibold"
                                                        :class="showAnswers && alt.is_correct ? 'bg-green-100 border-green-500 text-green-700' : ''">
                                                        {{ String.fromCharCode(65 + altIndex) }}
                                                    </div>
                                                    <div class="flex-1 text-sm text-gray-800 leading-relaxed">
                                                        {{ alt.content }}
                                                        <span v-if="showAnswers && alt.is_correct"
                                                            class="ml-2 text-green-600 font-semibold inline-flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            Correta
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Answer Space for Dissertative -->
                                            <div v-else-if="exam.format_config?.show_answer_space !== false"
                                                class="ml-11 mt-3 space-y-2">
                                                <div class="border-b border-gray-300 py-2" v-for="line in 4"
                                                    :key="line">
                                                    &nbsp;
                                                </div>
                                            </div>

                                            <!-- Explanation (if showing answers) -->
                                            <div v-if="showAnswers && question.explanation"
                                                class="ml-11 mt-3 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                                                <p class="text-xs font-semibold text-blue-900 mb-1">Explicação:</p>
                                                <p class="text-sm text-blue-800">{{ question.explanation }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="mt-12 pt-6 border-t-2 border-gray-300 text-center">
                                        <p v-if="exam.footer_config?.custom_text" class="text-sm text-gray-600 mb-2">
                                            {{ exam.footer_config.custom_text }}
                                        </p>

                                        <p v-if="exam.footer_config?.show_page_number" class="text-xs text-gray-500">
                                            Página 1
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Folha de Respostas (se habilitado) -->
                            <div v-if="exam.format_config?.separate_answer_sheet && hasMultipleChoice"
                                class="mx-auto bg-white shadow-lg mt-8" :class="getPageOrientationClass()"
                                :style="getPageStyle()">
                                <div :style="getContentStyle()">
                                    <div class="text-center mb-6 pb-4 border-b-2 border-gray-300">
                                        <h2 class="text-xl font-bold">FOLHA DE RESPOSTAS</h2>
                                        <p class="text-sm text-gray-600 mt-1">{{ exam.title }}</p>
                                    </div>

                                    <div class="space-y-4 mb-6">
                                        <div class="border-b pb-3">
                                            <p class="text-sm">Nome: _______________________________________________</p>
                                            <p class="text-sm mt-2">Turma: ____________________ Data: ____/____/________
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div v-for="(question, index) in multipleChoiceQuestions" :key="question.id"
                                            class="flex items-center gap-3">
                                            <span class="font-semibold w-8">{{ index + 1 }}.</span>
                                            <div class="flex gap-2">
                                                <div v-for="letter in ['A', 'B', 'C', 'D', 'E']" :key="letter"
                                                    class="w-8 h-8 border-2 border-gray-400 rounded flex items-center justify-center text-sm font-semibold">
                                                    {{ letter }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="no-print flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">{{ questions.length }}</span> questões •
                                <span class="font-medium">{{ totalPoints }}</span> pontos
                            </div>

                            <div class="flex items-center gap-3">
                                <button @click="$emit('edit')"
                                    class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Editar
                                </button>

                                <button @click="handleExportPDF" :disabled="isDownloading"
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg v-if="!isDownloading" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ isDownloading && downloadType === 'pdf' ? 'Gerando PDF...' : 'Exportar PDF' }}
                                </button>

                                <button @click="handleExportDOCX" :disabled="isDownloading"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg v-if="!isDownloading" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ isDownloading && downloadType === 'docx' ? 'Gerando DOCX...' : 'Exportar DOCX' }}
                                </button>
                            </div>
                        </div>

                        <!-- Loading Overlay dentro do Modal -->
                        <Transition name="fade">
                            <div v-if="isDownloading"
                                class="absolute inset-0 bg-white bg-opacity-95 flex items-center justify-center z-10 rounded-lg">
                                <div class="text-center">
                                    <div class="mb-4 flex justify-center">
                                        <svg class="animate-spin h-16 w-16 text-blue-600"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">
                                        {{ downloadType === 'pdf' ? 'Gerando PDF...' : 'Gerando DOCX...' }}
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-4">
                                        {{ showAnswers ? 'Com gabarito' : 'Sem gabarito' }}
                                    </p>
                                    <div class="flex justify-center space-x-1">
                                        <div class="w-3 h-3 bg-blue-600 rounded-full animate-bounce"></div>
                                        <div class="w-3 h-3 bg-blue-600 rounded-full animate-bounce delay-150"></div>
                                        <div class="w-3 h-3 bg-blue-600 rounded-full animate-bounce delay-300"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-4">
                                        O download começará em instantes...
                                    </p>
                                </div>
                            </div>
                        </Transition>
        </div>
    </Modal>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    exam: {
        type: Object,
        required: true
    },
    questions: {
        type: Array,
        required: true
    },
    questionTypes: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'edit', 'export-pdf', 'export-docx']);

// Estados
const showAnswers = ref(false);
const zoom = ref(100);
const isDownloading = ref(false);
const downloadType = ref('');
const previewContent = ref(null);
const previewContainer = ref(null);

// Computed
const totalPoints = computed(() => {
    return props.questions.reduce((sum, q) => {
        const points = getQuestionPoints(q);
        return sum + Number(points);
    }, 0);
});

const displayQuestions = computed(() => {
    let questions = [...props.questions];

    // Embaralhar questões se configurado
    if (props.exam.format_config?.shuffle_questions) {
        questions = shuffleArray(questions);
    }

    return questions;
});

const multipleChoiceQuestions = computed(() => {
    return props.questions.filter(q => q.question_type_id === 1);
});

const hasMultipleChoice = computed(() => {
    return multipleChoiceQuestions.value.length > 0;
});

// Methods
const getPageOrientationClass = () => {
    return props.exam.format_config?.orientation === 'landscape'
        ? 'page-landscape'
        : 'page-portrait';
};

const getPageStyle = () => {
    return {
        transform: `scale(${zoom.value / 100})`,
        transformOrigin: 'top center'
    };
};

const getContentStyle = () => {
    const margins = {
        narrow: '1.27cm',
        normal: '2.5cm',
        wide: '3.17cm'
    };

    const margin = margins[props.exam.format_config?.margins] || margins.normal;

    return {
        fontFamily: props.exam.format_config?.font_family || 'Arial',
        fontSize: props.exam.format_config?.font_size || '12pt',
        lineHeight: props.exam.format_config?.line_spacing || '1.5',
        padding: margin,
        minHeight: props.exam.format_config?.orientation === 'landscape' ? '21cm' : '29.7cm'
    };
};

const getAlternatives = (question) => {
    let alternatives = question.alternatives || [];

    // Embaralhar alternativas se configurado
    if (props.exam.format_config?.shuffle_alternatives) {
        alternatives = shuffleArray([...alternatives]);
    }

    return alternatives;
};

const shuffleArray = (array) => {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
};

const closePreview = () => {
    emit('close');
};

const zoomIn = () => {
    if (zoom.value < 150) {
        zoom.value += 10;
    }
};

const zoomOut = () => {
    if (zoom.value > 30) {
        zoom.value -= 10;
    }
};

// Ajusta o zoom automaticamente para caber na tela ao abrir — antes a
// página A4 de largura fixa (21cm ≈ 794px) sempre abria a 100%, garantindo
// overflow horizontal em qualquer tela menor que isso (todo celular).
const fitToContainer = () => {
    nextTick(() => {
        const content = previewContent.value;
        const container = previewContainer.value;
        if (!content || !container) return;

        // offsetWidth ignora o transform: scale já aplicado, é a largura
        // "real" da página antes de qualquer zoom.
        const naturalWidth = content.offsetWidth;
        const availableWidth = container.clientWidth - 48; // folga de padding
        if (naturalWidth <= 0 || availableWidth <= 0) return;

        const fitRatio = Math.min(1, availableWidth / naturalWidth);
        zoom.value = Math.max(30, Math.min(100, Math.round(fitRatio * 100)));
    });
};

let resizeTimeout;
const handleResize = () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(fitToContainer, 150);
};

onMounted(() => {
    fitToContainer();
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    clearTimeout(resizeTimeout);
});

const handleExportPDF = () => {
    isDownloading.value = true;
    downloadType.value = 'pdf';

    emit('export-pdf', showAnswers.value);

    setTimeout(() => {
        isDownloading.value = false;
        downloadType.value = '';
    }, 3000);
};

const handleExportDOCX = () => {
    isDownloading.value = true;
    downloadType.value = 'docx';

    emit('export-docx', showAnswers.value);

    setTimeout(() => {
        isDownloading.value = false;
        downloadType.value = '';
    }, 3000);
};

const getQuestionPoints = (question) => {
    const points = question.points_override || question.points || 1;
    return parseFloat(points) || 0;
};

const getQuestionTypeName = (typeId) => {
    const type = props.questionTypes.find(t => t.id === typeId);
    return type?.name || 'Questão';
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};
</script>

<style scoped>
/* Fade transition for loading overlay */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Page sizes */
.page-portrait {
    width: 21cm;
    min-height: 29.7cm;
}

.page-landscape {
    width: 29.7cm;
    min-height: 21cm;
}

/* Columns layout */
.exam-columns-2 {
    column-count: 2;
    column-gap: 2rem;
}

/* Question spacing */
.question-block {
    break-inside: avoid;
    page-break-inside: avoid;
}

/* Print Styles */
@media print {

    .modal-overlay,
    .no-print,
    button {
        display: none !important;
    }

    .page-portrait,
    .page-landscape {
        transform: none !important;
        width: 100% !important;
        box-shadow: none !important;
    }
}
</style>