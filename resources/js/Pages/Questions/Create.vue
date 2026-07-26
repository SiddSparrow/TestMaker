<template>
    <AppLayout>
        <Head title="Nova Questão" />

        <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div v-if="Object.keys(form.errors).length > 0"
                 class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <h3 class="text-red-800 font-bold mb-2">Erros de validação</h3>
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    <li v-for="(error, field) in form.errors" :key="field">
                        <strong>{{ humanizeField(field) }}:</strong> {{ error }}
                    </li>
                </ul>
            </div>
            <PageHeader title="Nova Questão" subtitle="Preencha os campos abaixo para criar uma nova questão"
                        :breadcrumb="[{ label: 'Questões', href: route('questions.index') }, { label: 'Nova Questão' }]" class="mb-6" />

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Informações Básicas -->
                <QuestionFormFields
                        v-model:subject-id="form.subject_id"
                        v-model:topic-id="form.topic_id"
                        v-model:question-type-id="form.question_type_id"
                        v-model:difficulty-level="form.difficulty_level"
                        v-model:points="form.points"
                        v-model:is-active="form.is_active"
                        :subjects="subjects"
                        :topics="topics"
                        :question-types="question_types"
                        :errors="form.errors"
                        @question-type-change="onQuestionTypeChange"
                    />

                <!-- Enunciado -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Enunciado</h2>

                    <FormField label="Enunciado da questão" required :error="form.errors.statement" v-slot="{ id }">
                        <textarea v-model="form.statement"
                                  :id="id"
                                  rows="5"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  :class="{ 'border-red-500': form.errors.statement }"
                                  placeholder="Digite o enunciado da questão..."></textarea>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ form.statement.length }} caracteres (mínimo 10)
                        </p>
                    </FormField>

                    <FormField label="Explicação/Resolução" class="mt-4" v-slot="{ id }">
                        <textarea v-model="form.explanation"
                                  :id="id"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Opcional: explicação da resposta correta..."></textarea>
                    </FormField>
                </div>

                <!-- Alternativas (apenas para tipos específicos) -->
                <AlternativesManager
                    v-if="showAlternatives"
                    v-model="form.alternatives"
                    :question-type="currentQuestionType"
                    :errors="form.errors"
                />

                <!-- Etiquetas -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Etiquetas</h2>

                    <FormField label="Adicionar etiquetas (opcional)" v-slot="{ id }">
                        <select :id="id" v-model="selectedTag"
                                @change="addTag"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione uma etiqueta</option>
                            <option v-for="tag in availableTags"
                                    :key="tag.id"
                                    :value="tag.id">
                                {{ tag.name }}
                            </option>
                        </select>
                    </FormField>

                    <div v-if="form.tags.length > 0" class="mt-3 flex flex-wrap gap-2">
                        <span v-for="tagId in form.tags"
                              :key="tagId"
                              class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                            {{ getTagName(tagId) }}
                            <button type="button"
                                    @click="removeTag(tagId)"
                                    class="ml-2 text-blue-600 hover:text-blue-800">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </span>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex justify-end space-x-3 pt-6">
                    <Link :href="route('questions.index')"
                       class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition-colors">
                        Cancelar
                    </Link>
                    <BaseButton type="submit" :loading="form.processing">
                        {{ form.processing ? 'Salvando...' : 'Salvar Questão' }}
                    </BaseButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AlternativesManager from '@/Components/AlternativesManager.vue';
import QuestionFormFields from '@/Components/QuestionFormFields.vue';
import FormField from '@/Components/UI/FormField.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { humanizeField } from '@/utils/fieldLabels';
import { useUnsavedChanges } from '@/composables/useUnsavedChanges';

const props = defineProps({
    subjects: Array,
    topics: Array,
    question_types: Array,
    tags: Array,
});

const form = useForm({
    statement: '',
    explanation: '',
    subject_id: '',
    topic_id: '',
    question_type_id: '',
    difficulty_level: 'medium',
    points: 1,
    is_active: true,
    alternatives: [
        { content: '', is_correct: false, order: 1 },
        { content: '', is_correct: false, order: 2 },
    ],
    tags: [],
});

useUnsavedChanges(() => form.isDirty && !form.processing);

const selectedTag = ref('');

// Verifica se deve mostrar alternativas
const showAlternatives = computed(() => {
    const type = props.question_types.find(t => t.id === form.question_type_id);
    if (!type) return false;
    
    const slug = type.slug.toLowerCase();
    return ['multipla-escolha', 'verdadeiro-falso', 'multipla-resposta'].includes(slug);
});

const currentQuestionType = computed(() => {
    return props.question_types.find(t => t.id === form.question_type_id) || {};
});

// Tags disponíveis (não selecionadas)
const availableTags = computed(() => {
    return props.tags.filter(tag => !form.tags.includes(tag.id));
});


// Quando muda o tipo de questão
const onQuestionTypeChange = () => {
    const type = props.question_types.find(t => t.id === form.question_type_id);
    
    if (type?.slug === 'dissertativa') {
        // Dissertativa não tem alternativas
        form.alternatives = [];
    } else if (form.alternatives.length === 0) {
        // Outros tipos começam com 2 alternativas vazias
        form.alternatives = [
            { content: '', is_correct: false, order: 1 },
            { content: '', is_correct: false, order: 2 },
        ];
    }
};

// Adiciona tag
const addTag = () => {
    if (selectedTag.value && !form.tags.includes(selectedTag.value)) {
        form.tags.push(selectedTag.value);
        selectedTag.value = '';
    }
};

// Remove tag
const removeTag = (tagId) => {
    const index = form.tags.indexOf(tagId);
    if (index > -1) {
        form.tags.splice(index, 1);
    }
};

// Pega nome da tag pelo ID
const getTagName = (tagId) => {
    return props.tags.find(tag => tag.id === tagId)?.name || '';
};

// Submit do formulário
const submit = () => {
    form.post(route('questions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        // Erros são automaticamente injetados em form.errors.
    });
};
</script>