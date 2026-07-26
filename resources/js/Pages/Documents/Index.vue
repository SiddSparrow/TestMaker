<template>
    <AppLayout>
        <Head title="Documentos" />

        <div class="space-y-6">
            <PageHeader title="Documentos" subtitle="Extraia questões de provas em PDF ou DOCX">
                <template #actions>
                    <BaseButton :href="route('documents.create')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M16 6l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Upload Documento
                    </BaseButton>
                </template>
            </PageHeader>

            <!-- Info sobre atualização automática -->
            <div v-if="hasProcessingDocuments" class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <svg class="animate-spin h-5 w-5 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-blue-800 text-sm">
                        <strong>Processamento em andamento.</strong> Esta página será atualizada automaticamente a cada 5 segundos.
                    </p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <FormField label="Buscar" v-slot="{ id }" class="md:col-span-2">
                        <input :id="id" v-model="form.search" type="text" placeholder="Buscar por nome do arquivo..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </FormField>
                    <FormField label="Status" v-slot="{ id }">
                        <select :id="id" v-model="form.status" @change="applyFilters"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos os status</option>
                            <option value="pending">Aguardando</option>
                            <option value="processing">Processando</option>
                            <option value="completed">Concluído</option>
                            <option value="failed">Falhou</option>
                        </select>
                    </FormField>
                </div>
            </div>

            <!-- Lista de Documentos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <DataTable :columns="columns" :rows="documents.data" :loading="isLoading">
                    <template #cell-file="{ row }">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-blue-100 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ row.original_name }}</div>
                                <div class="text-sm text-gray-500">{{ formatFileSize(row.file_size) }} • {{ row.file_type.toUpperCase() }}</div>
                            </div>
                        </div>
                    </template>

                    <template #cell-status="{ row }">
                        <StatusBadge :label="getStatusLabel(row.status)" :tone="getStatusTone(row.status)" />
                    </template>

                    <template #cell-questions="{ row }">
                        <span class="text-sm text-gray-500">{{ getQuestionCount(row) }}</span>
                    </template>

                    <template #cell-created_at="{ row }">
                        <span class="text-sm text-gray-500">{{ formatDate(row.created_at) }}</span>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-1">
                            <BaseButton v-if="row.status === 'completed'" :href="route('documents.show', row.id)"
                                        variant="outline" size="sm" icon-only :aria-label="`Revisar documento: ${row.original_name}`">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton v-if="row.status === 'failed'" variant="outline" size="sm" icon-only
                                        :aria-label="`Reprocessar documento: ${row.original_name}`"
                                        @click="confirmReprocess(row)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </BaseButton>
                            <BaseButton variant="outline" size="sm" icon-only
                                        :aria-label="`Excluir documento: ${row.original_name}`"
                                        class="!text-red-600 !border-red-300 hover:!bg-red-50"
                                        @click="confirmDelete(row)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </BaseButton>
                        </div>
                    </template>

                    <template #empty>
                        <EmptyState :title="hasFilters ? 'Nenhum documento encontrado' : 'Nenhum documento enviado ainda'"
                                    :description="hasFilters ? 'Tente ajustar a busca ou o filtro de status.' : 'Envie uma prova em PDF ou DOCX para começar.'">
                            <template #icon>
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </template>
                            <template #actions>
                                <BaseButton v-if="hasFilters" variant="secondary" @click="resetFilters">Limpar Filtros</BaseButton>
                                <BaseButton v-else :href="route('documents.create')">Enviar Primeiro Documento</BaseButton>
                            </template>
                        </EmptyState>
                    </template>
                </DataTable>

                <div v-if="documents.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <Pagination :links="documents.links"
                                :summary="`Mostrando ${documents.from} a ${documents.to} de ${documents.total} documentos`" />
                </div>
            </div>
        </div>

        <ConfirmDialog
            v-model:show="showDeleteConfirm"
            title="Excluir documento"
            :message='`Tem certeza que deseja excluir o documento "${documentToDelete?.original_name}"? Esta ação não pode ser desfeita.`'
            confirm-text="Sim, excluir"
            cancel-text="Cancelar"
            type="danger"
            @confirm="deleteDocument"
        />

        <ConfirmDialog
            v-model:show="showReprocessConfirm"
            title="Reprocessar documento"
            :message='`Deseja reprocessar o documento "${documentToReprocess?.original_name}"? A extração anterior será descartada.`'
            confirm-text="Sim, reprocessar"
            cancel-text="Cancelar"
            type="warning"
            @confirm="reprocessDocument"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, onBeforeUnmount, ref, watch } from 'vue';
import { debounce } from 'lodash-es';
import PageHeader from '@/Components/UI/PageHeader.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormField from '@/Components/UI/FormField.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    documents: Object,
    filters: { type: Object, default: () => ({}) },
});

const columns = [
    { key: 'file', label: 'Arquivo' },
    { key: 'status', label: 'Status' },
    { key: 'questions', label: 'Questões' },
    { key: 'created_at', label: 'Data' },
    { key: 'actions', label: 'Ações', align: 'right' },
];

const isLoading = ref(false);
const showDeleteConfirm = ref(false);
const documentToDelete = ref(null);
const showReprocessConfirm = ref(false);
const documentToReprocess = ref(null);

const form = useForm({
    search: props.filters.search || '',
    status: props.filters.status || '',
    per_page: props.filters.per_page || 15,
});

const hasFilters = computed(() => form.search !== '' || form.status !== '');

const hasProcessingDocuments = computed(() =>
    props.documents.data.some((doc) => doc.status === 'pending' || doc.status === 'processing')
);

const applyFilters = () => {
    form.get(route('documents.index'), { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
    form.reset();
    applyFilters();
};

watch(() => form.search, debounce(applyFilters, 500));

const getStatusLabel = (status) => ({
    pending: 'Aguardando',
    processing: 'Processando...',
    completed: 'Concluído',
    failed: 'Falhou',
}[status] || status);

const getStatusTone = (status) => ({
    pending: 'yellow',
    processing: 'blue',
    completed: 'green',
    failed: 'red',
}[status] || 'gray');

const getQuestionCount = (document) => {
    if (document.status !== 'completed' || !document.extraction_result) return '—';
    const count = document.extraction_result.questions?.length || 0;
    return `${count} questão${count !== 1 ? 'ões' : ''}`;
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const confirmReprocess = (document) => {
    documentToReprocess.value = document;
    showReprocessConfirm.value = true;
};

const reprocessDocument = () => {
    if (documentToReprocess.value) {
        router.post(route('documents.reprocess', documentToReprocess.value.id), {}, {
            onSuccess: () => { showReprocessConfirm.value = false; },
        });
    }
};

const confirmDelete = (document) => {
    documentToDelete.value = document;
    showDeleteConfirm.value = true;
};

const deleteDocument = () => {
    if (documentToDelete.value) {
        router.delete(route('documents.destroy', documentToDelete.value.id), {
            onSuccess: () => { showDeleteConfirm.value = false; },
        });
    }
};

// Auto-refresh enquanto houver documentos em processamento
const refreshInterval = ref(null);

const startAutoRefresh = () => {
    if (hasProcessingDocuments.value) {
        refreshInterval.value = setInterval(() => {
            router.reload({ only: ['documents'], preserveScroll: true });
        }, 5000);
    }
};

onMounted(startAutoRefresh);

onUnmounted(() => {
    if (refreshInterval.value) clearInterval(refreshInterval.value);
});

// Estado de carregamento durante navegações Inertia (filtro, paginação)
let stopStart;
let stopFinish;

onMounted(() => {
    stopStart = router.on('start', () => { isLoading.value = true; });
    stopFinish = router.on('finish', () => { isLoading.value = false; });
});

onBeforeUnmount(() => {
    stopStart?.();
    stopFinish?.();
});
</script>
