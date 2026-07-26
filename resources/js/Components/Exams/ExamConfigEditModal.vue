<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <div class="max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Configurações da Prova</h3>
                    <p class="text-sm text-gray-600 mt-1">Edite as informações básicas</p>
                </div>
                <button @click="close"
                        aria-label="Fechar"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <form @submit.prevent="save" class="p-6 space-y-6">
                            <!-- Título -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Título da Prova <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       v-model="localConfig.title"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       :class="{ 'border-red-500': errors.title }">
                                <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                            </div>

                            <!-- Descrição -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Descrição/Instruções
                                </label>
                                <textarea v-model="localConfig.description"
                                          rows="3"
                                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Adicione instruções para os alunos..."></textarea>
                            </div>

                            <!-- Data e Pontos -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Data da Prova
                                    </label>
                                    <input type="date"
                                           v-model="localConfig.exam_date"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Meta de Pontos
                                    </label>
                                    <input type="number"
                                           v-model.number="localConfig.target_total_points"
                                           min="1"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- Cabeçalho -->
                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">Cabeçalho da Prova</h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Nome da Escola/Instituição
                                        </label>
                                        <input type="text"
                                               v-model="localConfig.header_config.school_name"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="Ex: Colégio ABC">
                                    </div>

                                    <div class="flex items-center gap-6">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" 
                                                   v-model="localConfig.header_config.show_date"
                                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="text-sm text-gray-700">Mostrar data</span>
                                        </label>

                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" 
                                                   v-model="localConfig.header_config.show_student_info"
                                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="text-sm text-gray-700">Campos para aluno</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Rodapé -->
                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">Rodapé da Prova</h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Texto Customizado
                                        </label>
                                        <input type="text"
                                               v-model="localConfig.footer_config.custom_text"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="Ex: Boa prova!">
                                    </div>

                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" 
                                               v-model="localConfig.footer_config.show_page_number"
                                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Mostrar número de página</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                                <button type="button"
                                        @click="close"
                                        class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: Boolean,
    exam: Object,
    subjects: Array,
    topics: Array,
});

const emit = defineEmits(['update:show', 'update']);

const errors = ref({});

const localConfig = ref({
    title: '',
    description: '',
    exam_date: '',
    target_total_points: null,
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

// Watch para inicializar com dados do exam
watch(() => props.show, (newVal) => {
    if (newVal && props.exam) {
        localConfig.value = {
            title: props.exam.title || '',
            description: props.exam.description || '',
            exam_date: props.exam.exam_date || '',
            target_total_points: props.exam.target_total_points || null,
            header_config: props.exam.header_config || {
                school_name: '',
                show_date: true,
                show_student_info: true
            },
            footer_config: props.exam.footer_config || {
                custom_text: 'Boa prova!',
                show_page_number: true
            }
        };
        errors.value = {};
    }
});

const close = () => {
    emit('update:show', false);
};

const save = () => {
    errors.value = {};
    
    if (!localConfig.value.title) {
        errors.value.title = 'O título é obrigatório';
        return;
    }
    
    emit('update', localConfig.value);
    close();
};
</script>