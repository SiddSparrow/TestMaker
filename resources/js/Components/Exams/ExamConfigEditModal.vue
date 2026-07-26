<template>
    <AppModal :show="show" title="Configurações da Prova" max-width="2xl" @close="close">
        <div class="max-h-[80vh] overflow-y-auto">
            <p class="text-sm text-gray-600 -mt-2 mb-4">Edite as informações básicas</p>

            <!-- Content -->
            <form @submit.prevent="save" class="space-y-6">
                            <!-- Título -->
                            <FormField label="Título da Prova" required :error="errors.title" :id="ids.title">
                                <template #default="{ id }">
                                    <input type="text" :id="id"
                                           v-model="localConfig.title"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           :class="{ 'border-red-500': errors.title }">
                                </template>
                            </FormField>

                            <!-- Descrição -->
                            <FormField label="Descrição/Instruções" :id="ids.description">
                                <template #default="{ id }">
                                    <textarea :id="id" v-model="localConfig.description"
                                              rows="3"
                                              class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="Adicione instruções para os alunos..."></textarea>
                                </template>
                            </FormField>

                            <!-- Data e Pontos -->
                            <div class="grid grid-cols-2 gap-4">
                                <FormField label="Data da Prova" :id="ids.examDate">
                                    <template #default="{ id }">
                                        <input type="date" :id="id"
                                               v-model="localConfig.exam_date"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </template>
                                </FormField>

                                <FormField label="Meta de Pontos" :id="ids.targetPoints">
                                    <template #default="{ id }">
                                        <input type="number" :id="id"
                                               v-model.number="localConfig.target_total_points"
                                               min="1"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </template>
                                </FormField>
                            </div>

                            <!-- Cabeçalho -->
                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">Cabeçalho da Prova</h4>

                                <div class="space-y-4">
                                    <FormField label="Nome da Escola/Instituição" :id="ids.schoolName">
                                        <template #default="{ id }">
                                            <input type="text" :id="id"
                                                   v-model="localConfig.header_config.school_name"
                                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Ex: Colégio ABC">
                                        </template>
                                    </FormField>

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
                                    <FormField label="Texto Customizado" :id="ids.footerText">
                                        <template #default="{ id }">
                                            <input type="text" :id="id"
                                                   v-model="localConfig.footer_config.custom_text"
                                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Ex: Boa prova!">
                                        </template>
                                    </FormField>

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
                                        class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
        </div>
    </AppModal>
</template>

<script setup>
import { ref, watch, useId } from 'vue';
import AppModal from '@/Components/UI/AppModal.vue';
import FormField from '@/Components/UI/FormField.vue';

const props = defineProps({
    show: Boolean,
    exam: Object,
    subjects: Array,
    topics: Array,
});

const emit = defineEmits(['update:show', 'update']);

const uid = useId();
const ids = {
    title: `exam-config-title-${uid}`,
    description: `exam-config-description-${uid}`,
    examDate: `exam-config-date-${uid}`,
    targetPoints: `exam-config-target-points-${uid}`,
    schoolName: `exam-config-school-name-${uid}`,
    footerText: `exam-config-footer-text-${uid}`,
};

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