<template>
    <AppLayout>
        <Head title="Etiquetas" />

        <div class="space-y-6">
            <PageHeader title="Etiquetas" subtitle="Crie etiquetas para organizar suas questões"
                        :breadcrumb="[{ label: 'Etiquetas' }]" />

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    {{ editingTag ? 'Editar Etiqueta' : 'Nova Etiqueta' }}
                </h2>
                <form @submit.prevent="saveTag" class="flex flex-col sm:flex-row sm:items-end gap-4">
                    <FormField label="Nome da Etiqueta" required :error="form.errors.name" class="flex-1" v-slot="{ id }">
                        <input :id="id" type="text" v-model="form.name" required
                               placeholder="Ex: Importante, Revisão, Difícil"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </FormField>

                    <div class="flex gap-2">
                        <BaseButton v-if="editingTag" type="button" variant="secondary" @click="cancelEdit">
                            Cancelar
                        </BaseButton>
                        <BaseButton type="submit" :loading="form.processing">
                            {{ editingTag ? 'Atualizar' : 'Adicionar' }}
                        </BaseButton>
                    </div>
                </form>
            </div>

            <!-- List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <h2 class="text-sm font-semibold text-gray-700">Etiquetas Cadastradas ({{ filteredTags.length }})</h2>
                    <div class="relative sm:w-64">
                        <label for="tag-search" class="sr-only">Buscar etiqueta</label>
                        <input id="tag-search" type="text" v-model="searchQuery" placeholder="Buscar etiqueta..."
                               class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <EmptyState v-if="filteredTags.length === 0"
                            :title="searchQuery ? 'Nenhuma etiqueta encontrada' : 'Nenhuma etiqueta cadastrada ainda'">
                    <template #icon>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </template>
                </EmptyState>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="tag in filteredTags" :key="tag.id"
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

                        <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                            <BaseButton variant="outline" size="sm" icon-only :aria-label="`Editar etiqueta: ${tag.name}`" @click="editTag(tag)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton variant="danger-outline" size="sm" icon-only :aria-label="`Excluir etiqueta: ${tag.name}`"
                                        @click="deleteTag(tag)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            v-model:show="showDeleteConfirm"
            title="Excluir etiqueta"
            :message="deleteMessage"
            confirm-text="Sim, excluir"
            cancel-text="Cancelar"
            type="danger"
            @confirm="confirmDeleteTag"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import FormField from '@/Components/UI/FormField.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    tags: { type: Array, default: () => [] },
});

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
    return props.tags.filter((tag) => tag.name.toLowerCase().includes(query) || tag.slug.toLowerCase().includes(query));
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

const saveTag = () => {
    const data = { name: form.name, slug: generateSlug(form.name) };

    if (editingTag.value) {
        form.transform(() => data).put(route('tags.update', editingTag.value.id), {
            preserveScroll: true,
            onSuccess: cancelEdit,
        });
    } else {
        form.transform(() => data).post(route('tags.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
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
</script>
