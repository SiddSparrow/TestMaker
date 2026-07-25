<template>
    <AppLayout>
        <Head title="Upload de Documento" />

        <div class="space-y-6 fade-in max-w-3xl mx-auto">
            <!-- Cabeçalho -->
            <div class="slide-up" style="animation-delay: 100ms">
                <h1 class="page-title-elegant">Upload de Documento</h1>
                <p class="page-subtitle-elegant">Envie uma prova em PDF ou DOCX para extrair as questões automaticamente</p>
            </div>

            <!-- Card de Upload -->
            <div class="card-elegant slide-up" style="animation-delay: 150ms">
                <form @submit.prevent="submit">
                    <!-- Área de Upload -->
                    <div class="space-y-4">
                        <!-- Drag & Drop Area -->
                        <div
                            @dragover.prevent="dragover = true"
                            @dragleave.prevent="dragover = false"
                            @drop.prevent="onDrop"
                            :class="[
                                'border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer',
                                dragover ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400',
                                form.errors.document ? 'border-red-300 bg-red-50' : ''
                            ]"
                            @click="$refs.fileInput.click()"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".pdf,.docx,.txt"
                                @change="onFileChange"
                                class="hidden"
                            />

                            <div v-if="!selectedFile">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">
                                    <span class="font-semibold text-blue-600">Clique para selecionar</span> ou arraste um arquivo aqui
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    PDF, DOCX ou TXT até 10MB
                                </p>
                            </div>

                            <div v-else class="flex items-center justify-center gap-3">
                                <div class="flex-shrink-0">
                                    <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click.stop="removeFile"
                                    class="ml-auto text-red-600 hover:text-red-800"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="form.errors.document" class="text-sm text-red-600">
                            {{ form.errors.document }}
                        </div>

                        <!-- Informações -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-blue-800">
                                    <p class="font-semibold mb-1">Como funciona:</p>
                                    <ol class="list-decimal list-inside space-y-1 text-blue-700">
                                        <li>Faça upload de um documento com questões</li>
                                        <li>Nossa IA (Claude) extrairá as questões automaticamente</li>
                                        <li>Você poderá revisar e editar antes de importar</li>
                                        <li>As questões aprovadas serão adicionadas ao seu banco</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Dicas -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-2">💡 Dicas para melhor extração:</p>
                            <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                <li>Use documentos com texto selecionável (não imagens escaneadas)</li>
                                <li>Questões devem estar bem formatadas e numeradas</li>
                                <li>Alternativas devem estar identificadas (A, B, C, D ou I, II, III, IV)</li>
                                <li>Se possível, inclua o gabarito no documento</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
                        <a :href="route('documents.index')" class="btn-elegant btn-elegant-outline">
                            Cancelar
                        </a>
                        <button
                            type="submit"
                            :disabled="!selectedFile || form.processing"
                            class="btn-elegant btn-elegant-primary disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Enviando...' : 'Enviar e Processar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const dragover = ref(false);
const selectedFile = ref(null);
const fileInput = ref(null);

const form = useForm({
    document: null,
});

const onFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
        form.document = file;
    }
};

const onDrop = (event) => {
    dragover.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        selectedFile.value = file;
        form.document = file;
    }
};

const removeFile = () => {
    selectedFile.value = null;
    form.document = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const submit = () => {
    form.post(route('documents.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirecionar para lista de documentos após sucesso
            form.reset();
        },
    });
};
</script>
