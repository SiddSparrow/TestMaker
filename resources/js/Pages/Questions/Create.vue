<template>
    <Head title="Nova Questão" />
    
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Nova Questão</h1>
        
        <form @submit.prevent="submit">
            <!-- Seu formulário de criação aqui -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <div class="space-y-6">
                    <!-- Campos do formulário -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Enunciado da questão
                        </label>
                        <textarea v-model="form.statement"
                                  rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Digite o enunciado da questão..."></textarea>
                    </div>
                    
                    <!-- Botões -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a :href="route('questions.index')"
                           class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-md">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-md">
                            Salvar Questão
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

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

const submit = () => {
    form.post(route('questions.store'));
};
</script>