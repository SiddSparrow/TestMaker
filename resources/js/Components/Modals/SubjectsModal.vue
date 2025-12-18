<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" 
                 class="fixed inset-0 z-50 overflow-y-auto"
                 @click.self="closeModal">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
                    
                    <div class="relative bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Gerenciar Matérias</h3>
                                <p class="text-sm text-gray-600 mt-1">Crie e organize suas matérias</p>
                            </div>
                            <button @click="closeModal"
                                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <!-- Form -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                                    {{ editingSubject ? 'Editar Matéria' : 'Nova Matéria' }}
                                </h4>
                                <form @submit.prevent="saveSubject" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Nome da Matéria *
                                            </label>
                                            <input type="text" 
                                                   v-model="form.name"
                                                   required
                                                   placeholder="Ex: Matemática"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Cor
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="color" 
                                                       v-model="form.color"
                                                       class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                                                <input type="text" 
                                                       v-model="form.color"
                                                       placeholder="#3B82F6"
                                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Descrição
                                        </label>
                                        <textarea v-model="form.description"
                                                  rows="2"
                                                  placeholder="Descrição opcional da matéria"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500"></textarea>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button v-if="editingSubject"
                                                type="button"
                                                @click="cancelEdit"
                                                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                :disabled="form.processing"
                                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ editingSubject ? 'Atualizar' : 'Adicionar' }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- List -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                                    Matérias Cadastradas ({{ subjects.length }})
                                </h4>
                                
                                <div v-if="subjects.length === 0" class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <p>Nenhuma matéria cadastrada ainda</p>
                                </div>

                                <div v-else class="space-y-2">
                                    <div v-for="subject in subjects" 
                                         :key="subject.id"
                                         class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                                        <div class="flex items-center gap-3 flex-1">
                                            <div class="w-4 h-4 rounded-full flex-shrink-0" 
                                                 :style="{ backgroundColor: subject.color || '#6B7280' }"></div>
                                            <div class="flex-1 min-w-0">
                                                <h5 class="font-semibold text-gray-900">{{ subject.name }}</h5>
                                                <p v-if="subject.description" class="text-sm text-gray-600 truncate">
                                                    {{ subject.description }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ subject.topics_count || 0 }} tópicos • {{ subject.questions_count || 0 }} questões
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button @click="editSubject(subject)"
                                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                                                    title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button @click="deleteSubject(subject)"
                                                    class="p-2 text-red-600 hover:bg-red-50 rounded-md transition-colors"
                                                    title="Excluir">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <button @click="closeModal"
                                    class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    subjects: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:show']);

const editingSubject = ref(null);

const form = useForm({
    name: '',
    description: '',
    color: '#3B82F6',
});

const closeModal = () => {
    emit('update:show', false);
    cancelEdit();
};

const saveSubject = () => {
    if (editingSubject.value) {
        form.put(route('subjects.update', editingSubject.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                cancelEdit();
            }
        });
    } else {
        form.post(route('subjects.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            }
        });
    }
};

const editSubject = (subject) => {
    editingSubject.value = subject;
    form.name = subject.name;
    form.description = subject.description || '';
    form.color = subject.color || '#3B82F6';
};

const cancelEdit = () => {
    editingSubject.value = null;
    form.reset();
    form.clearErrors();
};

const deleteSubject = (subject) => {
    if (confirm(`Tem certeza que deseja excluir a matéria "${subject.name}"?\n\nTodos os tópicos e questões relacionados serão mantidos, mas não estarão mais vinculados a esta matéria.`)) {
        form.delete(route('subjects.destroy', subject.id), {
            preserveScroll: true,
        });
    }
};

watch(() => props.show, (newValue) => {
    if (!newValue) {
        cancelEdit();
    }
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.3s ease;
}

.modal-enter-from .relative {
    transform: scale(0.95);
}

.modal-leave-to .relative {
    transform: scale(0.95);
}
</style>