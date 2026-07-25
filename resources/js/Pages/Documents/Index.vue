<template>
    <AppLayout>
        <div class="space-y-6 fade-in">
            <!-- Cabeçalho -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 ml-mr-1">
                <div class="slide-up" style="animation-delay: 100ms">
                    <h1 class="page-title-elegant">Documentos</h1>
                    <p class="page-subtitle-elegant">Extraia questões de provas em PDF ou DOCX</p>
                </div>
                <div class="slide-up" style="animation-delay: 200ms">
                    <a :href="route('documents.create')"
                       class="btn-elegant btn-elegant-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Upload Documento
                    </a>
                </div>
            </div>

            <!-- Info sobre atualização automática -->
            <div v-if="hasProcessingDocuments()" class="slide-up bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg" style="animation-delay: 100ms">
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

            <!-- Lista de Documentos -->
            <div class="card-elegant slide-up" style="animation-delay: 150ms">
                <div v-if="documents.data.length === 0" class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-500 mb-4">Nenhum documento enviado ainda</p>
                    <a :href="route('documents.create')" class="btn-elegant btn-elegant-primary">
                        Enviar Primeiro Documento
                    </a>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Arquivo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Questões
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="document in documents.data" :key="document.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-blue-100 rounded-lg">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ document.original_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ formatFileSize(document.file_size) }} • {{ document.file_type.toUpperCase() }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusClass(document.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full items-center gap-1">
                                        <svg v-if="document.status === 'processing' || document.status === 'pending'" class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ getStatusLabel(document.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ getQuestionCount(document) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(document.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <a v-if="document.status === 'completed'"
                                       :href="route('documents.show', document.id)"
                                       class="text-blue-600 hover:text-blue-900">
                                        Revisar
                                    </a>
                                    <button v-if="document.status === 'failed'"
                                            @click="reprocess(document.id)"
                                            class="text-orange-600 hover:text-orange-900">
                                        Reprocessar
                                    </button>
                                    <button @click="deleteDocument(document.id)"
                                            class="text-red-600 hover:text-red-900">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div v-if="documents.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Mostrando {{ documents.from }} a {{ documents.to }} de {{ documents.total }} documentos
                        </div>
                        <div class="flex gap-2">
                            <a v-for="link in documents.links" :key="link.label"
                               :href="link.url"
                               v-html="link.label"
                               :class="[
                                   'px-3 py-1 text-sm border rounded',
                                   link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                   !link.url ? 'opacity-50 cursor-not-allowed' : ''
                               ]">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    documents: Object

});

// Auto-refresh se houver documentos em processamento
const refreshInterval = ref(null);

const hasProcessingDocuments = () => {
    return props.documents.data.some(doc =>
        doc.status === 'pending' || doc.status === 'processing'
    );
};

const startAutoRefresh = () => {
    if (hasProcessingDocuments()) {
        refreshInterval.value = setInterval(() => {
            router.reload({ only: ['documents'], preserveScroll: true });
        }, 5000); // Atualiza a cada 5 segundos
    }
};

onMounted(() => {
    startAutoRefresh();
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Aguardando',
        processing: 'Processando...',
        completed: 'Concluído',
        failed: 'Falhou',
    };
    return labels[status] || status;
};

const getQuestionCount = (document) => {
    if (document.status !== 'completed' || !document.extraction_result) {
        return '-';
    }
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
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const reprocess = (id) => {
    if (confirm('Deseja reprocessar este documento?')) {
        router.post(route('documents.reprocess', id));
    }
};

const deleteDocument = (id) => {
    if (confirm('Tem certeza que deseja excluir este documento? Esta ação não pode ser desfeita.')) {
        router.delete(route('documents.destroy', id));
    }
};
</script>
