<!-- resources/js/Pages/Exams/Create.vue -->
<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 px-4">
            <!-- STEP 1: Wizard de Configuração -->
            <ExamConfigForm
                v-if="!configCompleted"
                v-model="examConfig"
                :subjects="subjects"
                :topics="topics"
                @complete="handleConfigComplete"
                @cancel="handleCancel"
            />

            <!-- STEP 2: Builder de Questões (aparece após wizard) -->
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
                    v-model="form.questions"
                    :available-questions="questions"
                    :subjects="subjects"
                    :question-types="questionTypes"
                    :exam-title="examConfig.title"
                    :target-points="examConfig.target_total_points"
                    @reorder="handleReorder"
                    @points-change="handlePointsChange"
                />

                <div class="mt-6 flex justify-end gap-3">
                    <button @click="previewExam" 
                            class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        👁️ Visualizar
                    </button>
                    <button @click="saveExam" 
                            class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        💾 Salvar Prova
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ExamConfigForm from '@/Components/Exams/ExamConfigForm.vue';
import ExamBuilder from '@/Components/Exams/ExamBuilder.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    questions: Array,
    subjects: Array,
    topics: Array,
    questionTypes: Array,
});

const configCompleted = ref(false);
const examConfig = ref({});

const form = useForm({
    questions: [],
});

const handleConfigComplete = (config) => {
    examConfig.value = config;
    configCompleted.value = true;
    
    // Aqui você pode sugerir questões automaticamente baseado na config
    if (config.difficulty_distribution || config.topic_distribution.length > 0) {
        suggestQuestions(config);
    }
};

const editConfig = () => {
    configCompleted.value = false;
};

const handleCancel = () => {
    // Redirecionar ou mostrar modal de confirmação
};

const suggestQuestions = (config) => {
    // TODO: Chamar endpoint para sugerir questões
    console.log('Sugerindo questões baseado em:', config);
};

const saveExam = () => {
    const examData = {
        ...examConfig.value,
        questions: form.questions.map((q, index) => ({
            question_id: q.id,
            order: index + 1,
            points_override: q.points_override
        }))
    };
    console.log('Salvando exame com dados:', examData);
    form.post(route('exams.store', examData));
};

const previewExam = () => {
    // Abrir modal com preview
};
</script>