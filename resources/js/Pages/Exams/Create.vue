<template>
    <AppLayout>
        <Head title="Nova Prova" />

        <div class="max-w-7xl mx-auto py-6 px-4">
            <!-- Config Form -->
            <ExamConfigForm 
                v-if="!configCompleted"
                v-model="examConfig"
                :subjects="subjects"
                :topics="topics"
                @complete="handleConfigComplete"
                @cancel="handleCancel"
            />

            <!-- Builder -->
            <div v-else>
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ examConfig.title }}</h1>
                        <p class="text-sm text-gray-600 mt-1">{{ examConfig.description }}</p>
                    </div>
                    <button @click="editConfig"
                            class="px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md">
                        ← Editar Configurações
                    </button>
                </div>

                <ExamBuilder
                    v-model="examQuestions"
                    :available-questions="questions"
                    :subjects="subjects"
                    :question-types="questionTypes"
                    :exam-title="examConfig.title"
                    :target-points="examConfig.target_total_points"
                    @reorder="handleReorder"
                    @points-change="handlePointsChange"
                />

                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showPreview = true" 
                            :disabled="examQuestions.length === 0"
                            class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Visualizar Prova
                    </button>
                    
                    <button @click="saveExam" 
                            :disabled="examQuestions.length === 0"
                            class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        💾 Salvar Prova
                    </button>
                </div>
            </div>

            <!-- Preview Modal -->
            <ExamPreview
                v-if="showPreview"
                :show="showPreview"
                :exam="examData"
                :questions="examQuestions"
                :question-types="questionTypes"
                @close="showPreview = false"
                @edit="showPreview = false"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ExamConfigForm from '@/Components/Exams/ExamConfigForm.vue';
import ExamBuilder from '@/Components/Exams/ExamBuilder.vue';
import ExamPreview from '@/Components/Exams/ExamPreview.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    questions: {
        type: Array,
        default: () => []
    },
    subjects: {
        type: Array,
        default: () => []
    },
    topics: {
        type: Array,
        default: () => []
    },
    questionTypes: {
        type: Array,
        default: () => []
    }
});

// Estados

const showPreview = ref(false);
// examConfig atualizado com todas as configurações
const examConfig = ref({
    title: '',
    description: '',
    exam_date: '',
    main_subject_id: '',
    target_total_points: 100,
    target_question_count: null,
    difficulty_distribution: {
        easy: 0,
        medium: 0,
        hard: 0
    },
    topic_distribution: [],
    header_config: {
        school_name: '',
        show_date: true,
        show_student_info: true,
        show_logo: false
    },
    format_config: {
        // Texto
        font_size: '12pt',
        font_family: 'Arial',
        line_spacing: '1.5',
        justify_text: false,
        
        // Layout
        columns: 1,
        margins: 'normal',
        orientation: 'portrait',
        paper_size: 'A4',
        
        // Questões
        show_question_points: true,
        shuffle_questions: false,
        shuffle_alternatives: false,
        show_answer_space: true,
        separate_answer_sheet: false
    },
    footer_config: {
        custom_text: 'Boa prova!',
        show_page_number: true
    }
});

const examQuestions = ref([]);
const configCompleted = ref(false);

// Computed: dados da prova para preview
const examData = computed(() => ({
    title: examConfig.value.title || 'Nova Prova',
    description: examConfig.value.description || '',
    exam_date: examConfig.value.exam_date || null,
    main_subject_id: examConfig.value.main_subject_id || null,
    
    // Configurações de cabeçalho
    header_config: {
        school_name: examConfig.value.header_config?.school_name || '',
        show_date: examConfig.value.header_config?.show_date ?? true,
        show_student_info: examConfig.value.header_config?.show_student_info ?? true,
        show_logo: examConfig.value.header_config?.show_logo ?? false
    },
    
    // Configurações de formatação
    format_config: {
        font_size: examConfig.value.format_config?.font_size || '12pt',
        font_family: examConfig.value.format_config?.font_family || 'Arial',
        line_spacing: examConfig.value.format_config?.line_spacing || '1.5',
        justify_text: examConfig.value.format_config?.justify_text ?? false,
        columns: examConfig.value.format_config?.columns || 1,
        margins: examConfig.value.format_config?.margins || 'normal',
        orientation: examConfig.value.format_config?.orientation || 'portrait',
        paper_size: examConfig.value.format_config?.paper_size || 'A4',
        show_question_points: examConfig.value.format_config?.show_question_points ?? true,
        shuffle_questions: examConfig.value.format_config?.shuffle_questions ?? false,
        shuffle_alternatives: examConfig.value.format_config?.shuffle_alternatives ?? false,
        show_answer_space: examConfig.value.format_config?.show_answer_space ?? true,
        separate_answer_sheet: examConfig.value.format_config?.separate_answer_sheet ?? false
    },
    
    // Configurações de rodapé
    footer_config: {
        custom_text: examConfig.value.footer_config?.custom_text || 'Boa prova!',
        show_page_number: examConfig.value.footer_config?.show_page_number ?? true
    },
    
    // Distribuições configuradas
    difficulty_distribution: examConfig.value.difficulty_distribution || {},
    topic_distribution: examConfig.value.topic_distribution || [],
    target_total_points: examConfig.value.target_total_points || 100,
    target_question_count: examConfig.value.target_question_count || null,
    
    // Total de pontos calculado
    total_points: examQuestions.value.reduce((sum, q) => {
        return sum + (q.points_override || q.points || 0);
    }, 0)
}));

// Handlers
const handleConfigComplete = (config) => {
    examConfig.value = { ...examConfig.value, ...config };
    configCompleted.value = true;
    console.log('Configuração completa recebida:', examConfig.value);
};

const editConfig = () => {
    configCompleted.value = false;
};

const handleCancel = () => {
    router.visit(route('exams.index'));
};

const handleReorder = (questions) => {
    console.log('Questões reordenadas:', questions);
};

const handlePointsChange = (totalPoints) => {
    console.log('Total de pontos:', totalPoints);
};

const saveExam = () => {
    if (examQuestions.value.length === 0) {
        alert('Adicione pelo menos uma questão à prova');
        return;
    }

    const examPayload = {
        ...examConfig.value,
        questions: examQuestions.value.map((q, index) => ({
            question_id: q.id,
            order: index + 1,
            points_override: q.points_override || null
        })),
        total_points: examData.value.total_points
    };
    
    router.post(route('exams.store'), examPayload, {
        onSuccess: () => {
            console.log('Prova salva com sucesso!');
        },
        onError: (errors) => {
            console.error('Erros ao salvar:', errors);
        }
    });
};

const exportPDF = (withAnswers = false) => {
    if (!selectedExam.value) return;
    
    const url = route('exams.export.pdf', {
        exam: selectedExam.value.id,
        with_answers: withAnswers ? 1 : 0
    });
    
    // Usa window.location para forçar download
    window.location.href = url;
};

const exportDOCX = (withAnswers = false) => {
    if (!selectedExam.value) return;
    console.log('Exportando DOCX, com respostas:', withAnswers);
    const url = route('exams.export.docx', {
        exam: selectedExam.value.id,
        with_answers: withAnswers ? 1 : 0
    });
    
    // Usa window.location para forçar download
    window.location.href = url;
};
</script>