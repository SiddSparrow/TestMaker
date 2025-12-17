<template>
    <div class="bg-white rounded-lg shadow-lg border border-gray-200">
        <!-- Progress Steps -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center justify-between">
                <div v-for="(step, index) in steps" 
                     :key="index"
                     class="flex items-center"
                     :class="{ 'flex-1': index < steps.length - 1 }">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300"
                             :class="getStepClass(index)">
                            <svg v-if="index < currentStep" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span v-else class="text-sm font-semibold">{{ index + 1 }}</span>
                        </div>
                        <div class="ml-3 hidden sm:block">
                            <p class="text-sm font-medium" :class="index === currentStep ? 'text-blue-700' : 'text-gray-600'">
                                {{ step.title }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Connecting Line -->
                    <div v-if="index < steps.length - 1" 
                         class="flex-1 h-0.5 mx-4 transition-all duration-300"
                         :class="index < currentStep ? 'bg-blue-600' : 'bg-gray-300'"></div>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="p-6">
            <!-- STEP 1: Informações Básicas -->
            <div v-show="currentStep === 0" class="space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Informações da Prova</h2>
                    <p class="text-sm text-gray-600">Defina as informações básicas da sua prova</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Título da Prova <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               v-model="localConfig.title"
                               placeholder="Ex: Prova de Matemática - 1º Bimestre"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               :class="{ 'border-red-500': errors.title }">
                        <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Data da Prova
                        </label>
                        <input type="date"
                               v-model="localConfig.exam_date"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Matéria Principal <span class="text-red-500">*</span>
                        </label>
                        <select v-model="localConfig.main_subject_id"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                :class="{ 'border-red-500': errors.main_subject_id }">
                            <option value="">Selecione a matéria</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                {{ subject.name }}
                            </option>
                        </select>
                        <p v-if="errors.main_subject_id" class="mt-1 text-sm text-red-600">{{ errors.main_subject_id }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Descrição/Instruções
                        </label>
                        <textarea v-model="localConfig.description"
                                  rows="3"
                                  placeholder="Adicione instruções ou observações para os alunos..."
                                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Configuração de Pontos e Questões -->
            <div v-show="currentStep === 1" class="space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Configuração da Prova</h2>
                    <p class="text-sm text-gray-600">Configure pontos e quantidade de questões</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pontuação Total Desejada
                        </label>
                        <div class="relative">
                            <input type="number"
                                   v-model.number="localConfig.target_total_points"
                                   min="1"
                                   placeholder="100"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">pontos</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Deixe vazio para calcular automaticamente</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Quantidade de Questões
                        </label>
                        <input type="number"
                               v-model.number="localConfig.target_question_count"
                               min="1"
                               placeholder="10"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <p class="mt-1 text-xs text-gray-500">Quantidade ideal de questões</p>
                    </div>
                </div>

                <!-- Distribuição por Dificuldade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Distribuição por Dificuldade (opcional)
                    </label>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 border-2 rounded-lg transition-all"
                             :class="localConfig.difficulty_distribution.easy > 0 ? 'border-green-500 bg-green-50' : 'border-gray-200'">
                            <label class="block text-sm font-medium text-green-700 mb-2">
                                Fácil
                            </label>
                            <input type="number"
                                   v-model.number="localConfig.difficulty_distribution.easy"
                                   min="0"
                                   placeholder="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                            <p class="mt-1 text-xs text-gray-600">questões</p>
                        </div>

                        <div class="p-4 border-2 rounded-lg transition-all"
                             :class="localConfig.difficulty_distribution.medium > 0 ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200'">
                            <label class="block text-sm font-medium text-yellow-700 mb-2">
                                Médio
                            </label>
                            <input type="number"
                                   v-model.number="localConfig.difficulty_distribution.medium"
                                   min="0"
                                   placeholder="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-yellow-500 focus:border-yellow-500">
                            <p class="mt-1 text-xs text-gray-600">questões</p>
                        </div>

                        <div class="p-4 border-2 rounded-lg transition-all"
                             :class="localConfig.difficulty_distribution.hard > 0 ? 'border-red-500 bg-red-50' : 'border-gray-200'">
                            <label class="block text-sm font-medium text-red-700 mb-2">
                                Difícil
                            </label>
                            <input type="number"
                                   v-model.number="localConfig.difficulty_distribution.hard"
                                   min="0"
                                   placeholder="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500">
                            <p class="mt-1 text-xs text-gray-600">questões</p>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        Total configurado: <span class="font-semibold">{{ totalDifficultyCount }}</span> questões
                    </p>
                </div>
            </div>

            <!-- STEP 3: Distribuição por Tópicos -->
            <div v-show="currentStep === 2" class="space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Distribuição por Tópicos</h2>
                    <p class="text-sm text-gray-600">Escolha quantas questões de cada tópico (opcional)</p>
                </div>

                <div v-if="availableTopics.length > 0" class="space-y-3">
                    <div v-for="(topicConfig, index) in localConfig.topic_distribution"
                         :key="index"
                         class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <select v-model="topicConfig.topic_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Selecione um tópico</option>
                                    <option v-for="topic in getUnselectedTopics(index)" 
                                            :key="topic.id" 
                                            :value="topic.id">
                                        {{ topic.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="w-32">
                                <input type="number"
                                       v-model.number="topicConfig.question_count"
                                       min="1"
                                       placeholder="Qtd"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <button @click="removeTopicDistribution(index)"
                                    type="button"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button @click="addTopicDistribution"
                            type="button"
                            class="w-full px-4 py-2.5 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-colors">
                        + Adicionar Tópico
                    </button>

                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <strong>Total de questões por tópicos:</strong> {{ totalTopicCount }}
                        </p>
                    </div>
                </div>

                <div v-else class="text-center py-12 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="mt-2 text-sm">Nenhum tópico disponível para a matéria selecionada</p>
                    <p class="text-xs text-gray-400 mt-1">Volte e selecione uma matéria primeiro</p>
                </div>
            </div>

            <!-- STEP 4: Layout e Personalização -->
            <div v-show="currentStep === 3" class="space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Layout da Prova</h2>
                    <p class="text-sm text-gray-600">Personalize o cabeçalho e rodapé da prova</p>
                </div>

                <!-- Cabeçalho -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Cabeçalho</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nome da Escola/Instituição
                            </label>
                            <input type="text"
                                   v-model="localConfig.header_config.school_name"
                                   placeholder="Ex: Colégio ABC"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="flex items-end gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" 
                                       v-model="localConfig.header_config.show_date"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Mostrar data</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" 
                                       v-model="localConfig.header_config.show_student_info"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Campos para aluno</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Rodapé -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Rodapé</h3>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Texto Customizado
                        </label>
                        <input type="text"
                               v-model="localConfig.footer_config.custom_text"
                               placeholder="Ex: Boa prova!"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" 
                               v-model="localConfig.footer_config.show_page_number"
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Mostrar número de página</span>
                    </label>
                </div>

                <!-- Preview -->
                <div class="p-6 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                    <p class="text-sm font-medium text-gray-700 mb-3">Preview do Layout:</p>
                    <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
                        <div class="text-center mb-4 pb-4 border-b border-gray-200">
                            <h3 class="text-lg font-bold">{{ localConfig.header_config.school_name || 'Nome da Escola' }}</h3>
                            <p class="text-sm text-gray-600">{{ localConfig.title || 'Título da Prova' }}</p>
                            <p v-if="localConfig.header_config.show_date" class="text-xs text-gray-500 mt-1">
                                Data: {{ formatDate(localConfig.exam_date) }}
                            </p>
                            <div v-if="localConfig.header_config.show_student_info" class="mt-3 text-left text-xs text-gray-600 space-y-1">
                                <p>Nome: _______________________________________</p>
                                <p>Turma: _____________ Data: ___/___/___</p>
                            </div>
                        </div>
                        <div class="text-center text-gray-400 py-8 text-sm">
                            [Questões da prova aparecerão aqui]
                        </div>
                        <div class="text-center pt-4 border-t border-gray-200 text-xs text-gray-500">
                            <p>{{ localConfig.footer_config.custom_text || 'Boa prova!' }}</p>
                            <p v-if="localConfig.footer_config.show_page_number">Página 1</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
            <button v-if="currentStep > 0"
                    @click="previousStep"
                    type="button"
                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                ← Voltar
            </button>
            <div v-else></div>

            <div class="flex items-center gap-3">
                <button @click="$emit('cancel')"
                        type="button"
                        class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                    Cancelar
                </button>

                <button v-if="currentStep < steps.length - 1"
                        @click="nextStep"
                        type="button"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Próximo →
                </button>

                <button v-else
                        @click="submitConfig"
                        type="button"
                        class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                    Começar a Montar Prova
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({})
    },
    subjects: {
        type: Array,
        default: () => []
    },
    topics: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue', 'complete', 'cancel']);

const steps = [
    { title: 'Informações', icon: 'info' },
    { title: 'Configuração', icon: 'settings' },
    { title: 'Tópicos', icon: 'list' },
    { title: 'Layout', icon: 'layout' }
];

const currentStep = ref(0);
const errors = ref({});

const localConfig = ref({
    title: '',
    description: '',
    exam_date: '',
    main_subject_id: '',
    target_total_points: null,
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
        show_student_info: true
    },
    footer_config: {
        custom_text: 'Boa prova!',
        show_page_number: true
    },
    ...props.modelValue
});

// Computed
const availableTopics = computed(() => {
    if (!localConfig.value.main_subject_id) return [];
    return props.topics.filter(t => t.subject_id == localConfig.value.main_subject_id);
});

const totalDifficultyCount = computed(() => {
    const dist = localConfig.value.difficulty_distribution;
    return (dist.easy || 0) + (dist.medium || 0) + (dist.hard || 0);
});

const totalTopicCount = computed(() => {
    return localConfig.value.topic_distribution.reduce((sum, t) => sum + (t.question_count || 0), 0);
});

// Watch para emitir mudanças
watch(localConfig, (newVal) => {
    emit('update:modelValue', newVal);
}, { deep: true });

// Methods
const getStepClass = (index) => {
    if (index < currentStep.value) {
        return 'bg-blue-600 text-white border-blue-600';
    }
    if (index === currentStep.value) {
        return 'bg-white text-blue-600 border-blue-600';
    }
    return 'bg-white text-gray-400 border-gray-300';
};

const nextStep = () => {
    if (validateCurrentStep()) {
        currentStep.value++;
    }
};

const previousStep = () => {
    currentStep.value--;
    errors.value = {};
};

const validateCurrentStep = () => {
    errors.value = {};
    
    if (currentStep.value === 0) {
        if (!localConfig.value.title) {
            errors.value.title = 'O título é obrigatório';
        }
        if (!localConfig.value.main_subject_id) {
            errors.value.main_subject_id = 'Selecione uma matéria';
        }
    }
    
    return Object.keys(errors.value).length === 0;
};

const submitConfig = () => {
    if (validateCurrentStep()) {
        emit('complete', localConfig.value);
    }
};

const addTopicDistribution = () => {
    localConfig.value.topic_distribution.push({
        topic_id: '',
        question_count: 1
    });
};

const removeTopicDistribution = (index) => {
    localConfig.value.topic_distribution.splice(index, 1);
};

const getUnselectedTopics = (currentIndex) => {
    const selectedIds = localConfig.value.topic_distribution
        .map((t, i) => i !== currentIndex ? t.topic_id : null)
        .filter(id => id);
    
    return availableTopics.value.filter(t => !selectedIds.includes(t.id));
};

const formatDate = (date) => {
    if (!date) return 'DD/MM/AAAA';
    return new Date(date).toLocaleDateString('pt-BR');
};
</script>

<style scoped>
/* Smooth transitions */
input, select, textarea {
    transition: all 0.2s ease;
}

/* Progress line animation */
:deep(.flex-1.h-0\.5) {
    transition: background-color 0.3s ease;
}
</style>