<template>
    <Modal :show="show" max-width="4xl" @close="closeModal">
        <div class="max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Gerenciar Tópicos</h3>
                    <p class="text-sm text-gray-600 mt-1">Organize tópicos dentro das matérias</p>
                </div>
                <button @click="closeModal"
                        aria-label="Fechar"
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
                                    {{ editingTopic ? 'Editar Tópico' : 'Novo Tópico' }}
                                </h4>
                                <form @submit.prevent="saveTopic" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Matéria *
                                            </label>
                                            <select v-model="form.subject_id"
                                                    required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                                <option value="">Selecione uma matéria</option>
                                                <option v-for="subject in subjects" 
                                                        :key="subject.id" 
                                                        :value="subject.id">
                                                    {{ subject.name }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors.subject_id" class="mt-1 text-xs text-red-600">{{ form.errors.subject_id }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Nome do Tópico *
                                            </label>
                                            <input type="text" 
                                                   v-model="form.name"
                                                   required
                                                   placeholder="Ex: Equações do 2º grau"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Descrição
                                        </label>
                                        <textarea v-model="form.description"
                                                  rows="2"
                                                  placeholder="Descrição opcional do tópico"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500"></textarea>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button v-if="editingTopic"
                                                type="button"
                                                @click="cancelEdit"
                                                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                :disabled="form.processing"
                                                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ editingTopic ? 'Atualizar' : 'Adicionar' }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Filter -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por matéria</label>
                                <select v-model="filterSubjectId"
                                        class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    <option value="">Todas as matérias</option>
                                    <option v-for="subject in subjects" 
                                            :key="subject.id" 
                                            :value="subject.id">
                                        {{ subject.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- List -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                                    Tópicos Cadastrados ({{ filteredTopics.length }})
                                </h4>
                                
                                <div v-if="filteredTopics.length === 0" class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                    </svg>
                                    <p>{{ filterSubjectId ? 'Nenhum tópico nesta matéria' : 'Nenhum tópico cadastrado ainda' }}</p>
                                </div>

                                <div v-else class="space-y-2">
                                    <div v-for="topic in filteredTopics" 
                                         :key="topic.id"
                                         class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                      :style="{ 
                                                          backgroundColor: getSubjectColor(topic.subject_id) + '20',
                                                          color: getSubjectColor(topic.subject_id)
                                                      }">
                                                    {{ getSubjectName(topic.subject_id) }}
                                                </span>
                                            </div>
                                            <h5 class="font-semibold text-gray-900">{{ topic.name }}</h5>
                                            <p v-if="topic.description" class="text-sm text-gray-600 truncate">
                                                {{ topic.description }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ topic.questions_count || 0 }} questões
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-2 ml-4">
                                            <button @click="editTopic(topic)"
                                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                                                    title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button @click="deleteTopic(topic)"
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
    </Modal>

    <ConfirmDialog
        v-model:show="showDeleteConfirm"
        title="Excluir tópico"
        :message="deleteMessage"
        confirm-text="Sim, excluir"
        cancel-text="Cancelar"
        type="danger"
        @confirm="confirmDeleteTopic"
    />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    show: Boolean,
    topics: {
        type: Array,
        default: () => []
    },
    subjects: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:show']);

const editingTopic = ref(null);
const filterSubjectId = ref('');
const showDeleteConfirm = ref(false);
const topicToDelete = ref(null);
const deleteMessage = computed(() => {
    const name = topicToDelete.value?.name || '';
    return `Tem certeza que deseja excluir o tópico "${name}"? As questões relacionadas serão mantidas, mas não estarão mais vinculadas a este tópico.`;
});

const form = useForm({
    subject_id: '',
    name: '',
    description: '',
});

const filteredTopics = computed(() => {
    if (!filterSubjectId.value) return props.topics;
    return props.topics.filter(topic => topic.subject_id === filterSubjectId.value);
});

const closeModal = () => {
    emit('update:show', false);
    cancelEdit();
};

const saveTopic = () => {
    if (editingTopic.value) {
        form.put(route('topics.update', editingTopic.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                cancelEdit();
            }
        });
    } else {
        form.post(route('topics.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            }
        });
    }
};

const editTopic = (topic) => {
    editingTopic.value = topic;
    form.subject_id = topic.subject_id;
    form.name = topic.name;
    form.description = topic.description || '';
};

const cancelEdit = () => {
    editingTopic.value = null;
    form.reset();
    form.clearErrors();
};

const deleteTopic = (topic) => {
    topicToDelete.value = topic;
    showDeleteConfirm.value = true;
};

const confirmDeleteTopic = () => {
    if (!topicToDelete.value) return;

    form.delete(route('topics.destroy', topicToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            topicToDelete.value = null;
        },
    });
};

const getSubjectName = (subjectId) => {
    return props.subjects.find(s => s.id === subjectId)?.name || '';
};

const getSubjectColor = (subjectId) => {
    return props.subjects.find(s => s.id === subjectId)?.color || '#6B7280';
};

watch(() => props.show, (newValue) => {
    if (!newValue) {
        cancelEdit();
        filterSubjectId.value = '';
    }
});
</script>