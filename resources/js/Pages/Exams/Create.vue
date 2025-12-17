<template>
    <AppLayout>
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
                @export-pdf="exportPDF"
                @export-docx="exportDOCX"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
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
const configCompleted = ref(false);
const showPreview = ref(false);
const examConfig = ref({
    title: '',
    description: '',
    exam_date: '',
    main_subject_id: '',
    target_total_points: 100,
    header_config: {
        school_name: '',
        show_date: true,
        show_student_info: true
    },
    footer_config: {
        custom_text: 'Boa prova!',
        show_page_number: true
    }
});
const examQuestions = ref([]);

// Computed: dados da prova para preview
const examData = computed(() => ({
    title: examConfig.value.title || 'Nova Prova',
    description: examConfig.value.description || '',
    exam_date: examConfig.value.exam_date || null,
    header_config: examConfig.value.header_config || {},
    footer_config: examConfig.value.footer_config || {},
    total_points: examQuestions.value.reduce((sum, q) => {
        return sum + (q.points_override || q.points || 0);
    }, 0)
}));

// Handlers
const handleConfigComplete = (config) => {
    examConfig.value = config;
    configCompleted.value = true;
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

const exportPDF = (withAnswers) => {
    console.log('Exportar PDF', withAnswers ? 'com gabarito' : 'sem gabarito');
    // TODO: Implementar quando a prova estiver salva
    alert('Salve a prova primeiro para exportar');
};

const exportDOCX = (withAnswers) => {
    console.log('Exportar DOCX', withAnswers ? 'com gabarito' : 'sem gabarito');
    // TODO: Implementar quando a prova estiver salva
    alert('Salve a prova primeiro para exportar');
};
</script>