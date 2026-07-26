<template>
    <AppLayout>
        <Head title="Tópicos" />

        <div class="space-y-6">
            <PageHeader title="Tópicos" subtitle="Organize tópicos dentro das matérias"
                        :breadcrumb="[{ label: 'Tópicos' }]" />

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    {{ editingTopic ? 'Editar Tópico' : 'Novo Tópico' }}
                </h2>
                <form @submit.prevent="saveTopic" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormField label="Matéria" required :error="form.errors.subject_id" v-slot="{ id }">
                            <select :id="id" v-model="form.subject_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Selecione uma matéria</option>
                                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                            </select>
                        </FormField>

                        <FormField label="Nome do Tópico" required :error="form.errors.name" v-slot="{ id }">
                            <input :id="id" type="text" v-model="form.name" required
                                   placeholder="Ex: Equações do 2º grau"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </FormField>
                    </div>

                    <FormField label="Descrição" v-slot="{ id }">
                        <textarea :id="id" v-model="form.description" rows="2"
                                  placeholder="Descrição opcional do tópico"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </FormField>

                    <div class="flex justify-end gap-2">
                        <BaseButton v-if="editingTopic" type="button" variant="secondary" @click="cancelEdit">
                            Cancelar
                        </BaseButton>
                        <BaseButton type="submit" :loading="form.processing">
                            {{ editingTopic ? 'Atualizar' : 'Adicionar' }}
                        </BaseButton>
                    </div>
                </form>
            </div>

            <!-- List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <h2 class="text-sm font-semibold text-gray-700">Tópicos Cadastrados ({{ filteredTopics.length }})</h2>
                    <FormField label="Filtrar por matéria" class="sm:w-64" v-slot="{ id }">
                        <select :id="id" v-model="filterSubjectId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas as matérias</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                        </select>
                    </FormField>
                </div>

                <EmptyState v-if="filteredTopics.length === 0"
                            :title="filterSubjectId ? 'Nenhum tópico nesta matéria' : 'Nenhum tópico cadastrado ainda'">
                    <template #icon>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </template>
                </EmptyState>

                <div v-else class="space-y-2">
                    <div v-for="topic in filteredTopics" :key="topic.id"
                         class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                        <div class="flex-1 min-w-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mb-1"
                                  :style="{ backgroundColor: (topic.subject?.color || '#6B7280') + '20', color: topic.subject?.color || '#6B7280' }">
                                {{ topic.subject?.name || 'Sem matéria' }}
                            </span>
                            <h5 class="font-semibold text-gray-900">{{ topic.name }}</h5>
                            <p v-if="topic.description" class="text-sm text-gray-600 truncate">{{ topic.description }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ topic.questions_count || 0 }} questões</p>
                        </div>

                        <div class="flex items-center gap-1 ml-4">
                            <BaseButton variant="outline" size="sm" icon-only :aria-label="`Editar tópico: ${topic.name}`" @click="editTopic(topic)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton variant="danger-outline" size="sm" icon-only :aria-label="`Excluir tópico: ${topic.name}`"
                                        @click="deleteTopic(topic)">
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
            title="Excluir tópico"
            :message="deleteMessage"
            confirm-text="Sim, excluir"
            cancel-text="Cancelar"
            type="danger"
            @confirm="confirmDeleteTopic"
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
    topics: { type: Array, default: () => [] },
    subjects: { type: Array, default: () => [] },
});

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
    return props.topics.filter((topic) => topic.subject_id === filterSubjectId.value);
});

const saveTopic = () => {
    if (editingTopic.value) {
        form.put(route('topics.update', editingTopic.value.id), {
            preserveScroll: true,
            onSuccess: cancelEdit,
        });
    } else {
        form.post(route('topics.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
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
</script>
