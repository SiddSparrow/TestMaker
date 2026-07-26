<template>
    <AppLayout>
        <Head title="Matérias" />

        <div class="space-y-6">
            <PageHeader title="Matérias" subtitle="Crie e organize as matérias do seu banco de questões"
                        :breadcrumb="[{ label: 'Matérias' }]" />

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    {{ editingSubject ? 'Editar Matéria' : 'Nova Matéria' }}
                </h2>
                <form @submit.prevent="saveSubject" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormField label="Nome da Matéria" required :error="form.errors.name" v-slot="{ id }">
                            <input :id="id" type="text" v-model="form.name" required
                                   placeholder="Ex: Matemática"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </FormField>

                        <FormField label="Cor" v-slot="{ id }">
                            <div class="flex gap-2">
                                <input :id="id" type="color" v-model="form.color"
                                       class="h-10 w-16 rounded border border-gray-300 cursor-pointer">
                                <input type="text" v-model="form.color" placeholder="#3B82F6"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </FormField>
                    </div>

                    <FormField label="Descrição" v-slot="{ id }">
                        <textarea :id="id" v-model="form.description" rows="2"
                                  placeholder="Descrição opcional da matéria"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </FormField>

                    <div class="flex justify-end gap-2">
                        <BaseButton v-if="editingSubject" type="button" variant="secondary" @click="cancelEdit">
                            Cancelar
                        </BaseButton>
                        <BaseButton type="submit" :loading="form.processing">
                            {{ editingSubject ? 'Atualizar' : 'Adicionar' }}
                        </BaseButton>
                    </div>
                </form>
            </div>

            <!-- List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Matérias Cadastradas ({{ subjects.length }})</h2>

                <EmptyState v-if="subjects.length === 0" title="Nenhuma matéria cadastrada ainda">
                    <template #icon>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </template>
                </EmptyState>

                <div v-else class="space-y-2">
                    <div v-for="subject in subjects" :key="subject.id"
                         class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-4 h-4 rounded-full flex-shrink-0" :style="{ backgroundColor: subject.color || '#6B7280' }"></div>
                            <div class="flex-1 min-w-0">
                                <h5 class="font-semibold text-gray-900">{{ subject.name }}</h5>
                                <p v-if="subject.description" class="text-sm text-gray-600 truncate">{{ subject.description }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ subject.topics_count || 0 }} tópicos • {{ subject.questions_count || 0 }} questões
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <BaseButton variant="outline" size="sm" icon-only :aria-label="`Editar matéria: ${subject.name}`" @click="editSubject(subject)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton variant="outline" size="sm" icon-only :aria-label="`Excluir matéria: ${subject.name}`"
                                        class="!text-red-600 !border-red-300 hover:!bg-red-50" @click="deleteSubject(subject)">
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
            title="Excluir matéria"
            :message="deleteMessage"
            confirm-text="Sim, excluir"
            cancel-text="Cancelar"
            type="danger"
            @confirm="confirmDeleteSubject"
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

defineProps({
    subjects: { type: Array, default: () => [] },
});

const editingSubject = ref(null);
const showDeleteConfirm = ref(false);
const subjectToDelete = ref(null);
const deleteMessage = computed(() => {
    const name = subjectToDelete.value?.name || '';
    return `Tem certeza que deseja excluir a matéria "${name}"? Todos os tópicos e questões relacionados serão mantidos, mas não estarão mais vinculados a esta matéria.`;
});

const form = useForm({
    name: '',
    description: '',
    color: '#3B82F6',
});

const saveSubject = () => {
    if (editingSubject.value) {
        form.put(route('subjects.update', editingSubject.value.id), {
            preserveScroll: true,
            onSuccess: cancelEdit,
        });
    } else {
        form.post(route('subjects.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
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
    subjectToDelete.value = subject;
    showDeleteConfirm.value = true;
};

const confirmDeleteSubject = () => {
    if (!subjectToDelete.value) return;

    form.delete(route('subjects.destroy', subjectToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            subjectToDelete.value = null;
        },
    });
};
</script>
