<template>
    <Modal :show="show" max-width="4xl" @close="closeModal">
        <div class="max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Gerenciar Etiquetas</h3>
                    <p class="text-sm text-gray-600 mt-1">Crie etiquetas para organizar suas questões</p>
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
                                    {{ editingTag ? 'Editar Etiqueta' : 'Nova Etiqueta' }}
                                </h4>
                                <form @submit.prevent="saveTag" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Nome da Etiqueta *
                                        </label>
                                        <input type="text" 
                                               v-model="form.name"
                                               required
                                               placeholder="Ex: Importante, Revisão, Difícil"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                                        <!-- <p class="mt-1 text-xs text-gray-500">
                                            O slug será gerado automaticamente: <span class="font-mono">{{ generateSlug(form.name) }}</span>
                                        </p> -->
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button v-if="editingTag"
                                                type="button"
                                                @click="cancelEdit"
                                                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                :disabled="form.processing"
                                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ editingTag ? 'Atualizar' : 'Adicionar' }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Search -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar etiqueta</label>
                                <div class="relative">
                                    <input type="text" 
                                           v-model="searchQuery"
                                           placeholder="Digite para buscar..."
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- List -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                                    Etiquetas Cadastradas ({{ filteredTags.length }})
                                </h4>
                                
                                <div v-if="filteredTags.length === 0" class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <p>{{ searchQuery ? 'Nenhuma etiqueta encontrada' : 'Nenhuma etiqueta cadastrada ainda' }}</p>
                                </div>

                                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div v-for="tag in filteredTags" 
                                         :key="tag.id"
                                         class="group relative flex items-center justify-between p-4 bg-white border-2 border-gray-200 rounded-lg hover:border-blue-400 hover:shadow-md transition-all">
                                        <div class="flex-1 min-w-0 pr-2">
                                            <div class="flex items-center gap-2 mb-1">
                                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                </svg>
                                                <h5 class="font-semibold text-gray-900 truncate">{{ tag.name }}</h5>
                                            </div>
                                            <p class="text-xs text-gray-500 font-mono truncate">{{ tag.slug }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ tag.questions_count || 0 }} {{ tag.questions_count === 1 ? 'questão' : 'questões' }}
                                            </p>
                                        </div>

                                        <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="editTag(tag)"
                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                                    title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button @click="deleteTag(tag)"
                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"
                                                    title="Excluir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        title="Excluir etiqueta"
        :message="deleteMessage"
        confirm-text="Sim, excluir"
        cancel-text="Cancelar"
        type="danger"
        @confirm="confirmDeleteTag"
    />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    show: Boolean,
    tags: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:show']);

const editingTag = ref(null);
const searchQuery = ref('');
const showDeleteConfirm = ref(false);
const tagToDelete = ref(null);
const deleteMessage = computed(() => {
    const name = tagToDelete.value?.name || '';
    return `Tem certeza que deseja excluir a tag "${name}"? Esta ação não afetará as questões, apenas removerá a tag delas.`;
});

const form = useForm({
    name: '',
});

const filteredTags = computed(() => {
    if (!searchQuery.value) return props.tags;
    const query = searchQuery.value.toLowerCase();
    return props.tags.filter(tag => 
        tag.name.toLowerCase().includes(query) ||
        tag.slug.toLowerCase().includes(query)
    );
});

const generateSlug = (text) => {
    if (!text) return '';
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
};

const closeModal = () => {
    emit('update:show', false);
    cancelEdit();
};

const saveTag = () => {
    const data = {
        name: form.name,
        slug: generateSlug(form.name)
    };

    if (editingTag.value) {
        form.transform(() => data).put(route('tags.update', editingTag.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                cancelEdit();
            }
        });
    } else {
        form.transform(() => data).post(route('tags.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            }
        });
    }
};

const editTag = (tag) => {
    editingTag.value = tag;
    form.name = tag.name;
};

const cancelEdit = () => {
    editingTag.value = null;
    form.reset();
    form.clearErrors();
};

const deleteTag = (tag) => {
    tagToDelete.value = tag;
    showDeleteConfirm.value = true;
};

const confirmDeleteTag = () => {
    if (!tagToDelete.value) return;

    form.delete(route('tags.destroy', tagToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            tagToDelete.value = null;
        },
    });
};

watch(() => props.show, (newValue) => {
    if (!newValue) {
        cancelEdit();
        searchQuery.value = '';
    }
});
</script>