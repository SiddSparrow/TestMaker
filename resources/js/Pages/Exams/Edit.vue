<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 px-4">
            <!-- Header de Edição -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-xl shadow-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold mb-1">Editando Prova</h1>
                            <p class="text-blue-100 text-sm">{{ form.title }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button @click="showConfigModal = true"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 border border-white/30 rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Configurações
                        </button>

                        <button @click="showPreview = true"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 border border-white/30 rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Preview
                        </button>
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="grid grid-cols-4 gap-4 mt-6">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 border border-white/20">
                        <div class="text-white/70 text-xs mb-1">Questões</div>
                        <div class="text-2xl font-bold">{{ examQuestions.length }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 border border-white/20">
                        <div class="text-white/70 text-xs mb-1">Pontos</div>
                        <div class="text-2xl font-bold">{{ currentTotalPoints }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 border border-white/20">
                        <div class="text-white/70 text-xs mb-1">Média</div>
                        <div class="text-2xl font-bold">{{ averagePoints }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 border border-white/20">
                        <div class="text-white/70 text-xs mb-1">Meta</div>
                        <div class="text-2xl font-bold">{{ form.target_total_points || '--' }}</div>
                    </div>
                </div>
            </div>

            <!-- Status de Mudanças -->
            <div v-if="hasUnsavedChanges" 
                 class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-amber-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-amber-800">
                            Você tem alterações não salvas
                        </p>
                        <p class="text-sm text-amber-700 mt-1">
                            {{ changesSummary }}
                        </p>
                    </div>
                    <button @click="saveChanges"
                            :disabled="form.processing"
                            class="ml-4 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50">
                        Salvar Agora
                    </button>
                </div>
            </div>

            <!-- Builder -->
            <ExamBuilder
                v-model="examQuestions"
                :available-questions="questions"
                :subjects="subjects"
                :question-types="questionTypes"
                :exam-title="form.title"
                :target-points="form.target_total_points"
            />

            <!-- Actions Footer -->
            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Link :href="route('exams.show', exam.id)"
                              class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Cancelar
                        </Link>

                        <button @click="showDeleteConfirm = true"
                                class="inline-flex items-center px-4 py-2 text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Excluir Prova
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <div v-if="form.processing" class="text-sm text-gray-600 mr-2">
                            Salvando...
                        </div>

                        <button @click="saveAndContinue"
                                :disabled="form.processing || !hasUnsavedChanges"
                                class="inline-flex items-center px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            Salvar e Continuar
                        </button>

                        <button @click="saveAndExit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Salvar e Sair
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Config Modal -->
        <ExamConfigEditModal
            v-model:show="showConfigModal"
            :exam="exam"
            :subjects="subjects"
            :topics="topics"
            @update="handleConfigUpdate"
        />

        <!-- Preview Modal -->
        <ExamPreview
            v-if="showPreview"
            :show="showPreview"
            :exam="examDataForPreview"
            :questions="examQuestions"
            :question-types="questionTypes"
            @close="showPreview = false"
            @edit="showPreview = false"
            @export-pdf="exportPDF"
            @export-docx="exportDOCX"
        />

        <!-- Delete Confirmation -->
        <ConfirmDialog
            v-model:show="showDeleteConfirm"
            title="Excluir Prova"
            :message="`Tem certeza que deseja excluir a prova &quot;${exam.title}&quot;? Esta ação não pode ser desfeita.`"
            confirm-text="Sim, Excluir"
            cancel-text="Cancelar"
            type="danger"
            @confirm="deleteExam"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ExamBuilder from '@/Components/Exams/ExamBuilder.vue';
import ExamConfigEditModal from '@/Components/Exams/ExamConfigEditModal.vue';
import ExamPreview from '@/Components/Exams/ExamPreview.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    exam: Object,
    questions: Array,
    subjects: Array,
    topics: Array,
    questionTypes: Array,
});

// Estados
const showConfigModal = ref(false);
const showPreview = ref(false);
const showDeleteConfirm = ref(false);
const hasUnsavedChanges = ref(false);

// Questões do exame
const examQuestions = ref(props.exam.questions.map(q => ({
    ...q,
    points: Number(q.points) || 0,
    points_override: q.pivot?.points_override ? Number(q.pivot.points_override) : null,
    exam_order: Number(q.pivot?.order) || 0
})));

// Form
const form = useForm({
    title: props.exam.title,
    description: props.exam.description,
    exam_date: props.exam.exam_date,
    main_subject_id: props.exam.main_subject_id,
    target_total_points: props.exam.target_total_points,
    header_config: props.exam.header_config || {},
    format_config: props.exam.format_config || {},
    footer_config: props.exam.footer_config || {},
    questions: []
});

// Computed
const currentTotalPoints = computed(() => {
    return examQuestions.value.reduce((sum, q) => {
        const points = q.points_override || q.points || 0;
        return sum + Number(points);
    }, 0);
});

const averagePoints = computed(() => {
    if (examQuestions.value.length === 0) return 0;
    return (currentTotalPoints.value / examQuestions.value.length).toFixed(1);
});

const examDataForPreview = computed(() => ({
    ...props.exam,
    title: form.title,
    description: form.description,
    exam_date: form.exam_date,
    header_config: form.header_config,
    footer_config: form.footer_config,
    total_points: currentTotalPoints.value
}));

const changesSummary = computed(() => {
    const originalCount = props.exam.questions.length;
    const currentCount = examQuestions.value.length;
    const diff = currentCount - originalCount;
    
    if (diff > 0) return `${diff} questão(ões) adicionada(s)`;
    if (diff < 0) return `${Math.abs(diff)} questão(ões) removida(s)`;
    return 'Questões reordenadas ou pontos alterados';
});

// Watch para detectar mudanças e forçar recalculo
watch(examQuestions, (newVal) => {
    hasUnsavedChanges.value = true;
    
    // Força recalculo imediato
    nextTick(() => {
        // Trigger do computed
        const total = currentTotalPoints.value;
        console.log('Total recalculado:', total);
    });
}, { deep: true });

// Métodos
const markAsChanged = () => {
    hasUnsavedChanges.value = true;
};

const handleConfigUpdate = (config) => {
    form.title = config.title;
    form.description = config.description;
    form.exam_date = config.exam_date;
    form.target_total_points = config.target_total_points;
    form.header_config = config.header_config;
    form.footer_config = config.footer_config;
    hasUnsavedChanges.value = true;
    showConfigModal.value = false;
};

const saveChanges = () => {
    form.questions = examQuestions.value.map((q, index) => ({
        question_id: q.id,
        order: index + 1,
        points_override: q.points_override
    }));

    form.put(route('exams.update', props.exam.id), {
        preserveScroll: true,
        onSuccess: () => {
            hasUnsavedChanges.value = false;
        }
    });
};

const saveAndContinue = () => {
    saveChanges();
};

const saveAndExit = () => {
    form.questions = examQuestions.value.map((q, index) => ({
        question_id: q.id,
        order: index + 1,
        points_override: q.points_override
    }));

    form.put(route('exams.update', props.exam.id), {
        onSuccess: () => {
            router.visit(route('exams.show', props.exam.id));
        },
        onError: () => {
            // Permanecer na página de edição em caso de erro
            console.log('Erro ao salvar. Permanecendo na página de edição.');
        }
    });
};

const deleteExam = () => {
    router.delete(route('exams.destroy', props.exam.id), {
        onSuccess: () => {
            router.visit(route('exams.index'));
        }
    });
};

const exportPDF = (withAnswers) => {
    const url = `/exams/${props.exam.id}/export-pdf?with_answers=${withAnswers ? 1 : 0}`;
    window.open(url, '_blank');
};

const exportDOCX = (withAnswers) => {
    const url = `/exams/${props.exam.id}/export-docx?with_answers=${withAnswers ? 1 : 0}`;
    window.open(url, '_blank');
};

// Aviso ao sair com mudanças não salvas
window.addEventListener('beforeunload', (e) => {
    if (hasUnsavedChanges.value) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<style scoped>
/* Animações */
@keyframes slideIn {
    from {
        transform: translateY(-10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}
</style>