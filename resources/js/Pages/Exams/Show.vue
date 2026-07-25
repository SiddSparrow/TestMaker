<template>
    <AppLayout>
        <Head title="Detalhes da Prova" />

        <!-- Header -->
         <div class="container px-4">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white" style="border-radius: 10px;">
                <div class="container mx-auto px-4 py-8">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-medium">
                                    Exame
                                </span>
                                <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-medium">
                                    {{ exam.total_points }} pontos
                                </span>
                                <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-medium">
                                    {{ exam.questions.length }} questões
                                </span>
                            </div>
                            <h1 class="text-3xl font-bold mb-2">{{ exam.title }}</h1>
                            <p class="text-blue-100 mb-4 max-w-3xl">{{ exam.description }}</p>
                            
                            <div class="flex flex-wrap items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span v-if="exam.exam_date">{{ formatDate(exam.exam_date) }}</span>
                                    <span v-else>Sem data definida</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Criado em {{ formatDate(exam.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button @click="openPreview"
                                    class="inline-flex items-center justify-center px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-all border border-white/30 hover:border-white/50 group">
                                <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Visualizar Prova
                            </button>
                            
                            <Link :href="route('exams.edit', exam.id)"
                                class="inline-flex items-center justify-center px-5 py-2.5 bg-white text-blue-600 hover:bg-gray-50 font-medium rounded-lg transition-all border border-white group">
                                <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar Exame
                            </Link>
                            
                            <div class="relative group">
                                <button @click="toggleExportMenu"
                                        class="inline-flex items-center justify-center px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-lg transition-all">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Exportar
                                </button>
                                
                                <!-- Dropdown de Exportação -->
                                <div v-show="showExportMenu" 
                                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-10">
                                    <div class="py-1">
                                        <button @click="exportPDF(false)"
                                                class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                            <svg class="w-4 h-4 mr-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                                            </svg>
                                            Exportar PDF
                                        </button>
                                        <button @click="exportPDF(true)"
                                                class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                            <svg class="w-4 h-4 mr-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                                            </svg>
                                            PDF com Gabarito
                                        </button>
                                        <button @click="exportDOCX(false)"
                                                class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                            <svg class="w-4 h-4 mr-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                                            </svg>
                                            Exportar DOCX
                                        </button>
                                        <button @click="exportDOCX(true)"
                                                class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                            <svg class="w-4 h-4 mr-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                                            </svg>
                                            DOCX com Gabarito
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
         </div>
        

        <!-- Content -->
        <div class="container mx-auto px-4 py-6">
            <!-- Cards de Resumo -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Total de Questões</p>
                            <h3 class="text-2xl font-bold text-gray-900">{{ exam.questions.length }}</h3>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Pontuação Total</p>
                            <h3 class="text-2xl font-bold text-gray-900">{{ exam.total_points }}</h3>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Matérias Utilizadas</p>
                            <h3 class="text-2xl font-bold text-gray-900">{{ subjectsCount }}</h3>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Questões -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">Questões do Exame</h3>
                    <p class="text-sm text-gray-600 mt-1">Lista completa de questões ordenadas</p>
                </div>

                <div v-if="exam.questions.length === 0" class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-500 text-lg mb-2">Nenhuma questão adicionada</p>
                    <p class="text-gray-400 text-sm mb-4">Adicione questões para começar a construir sua prova</p>
                    <Link :href="route('exams.edit', exam.id)"
                          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Adicionar Questões
                    </Link>
                </div>

                <div v-else class="divide-y divide-gray-200">
                    <div v-for="(q, index) in exam.questions" :key="q.id" 
                         class="p-6 hover:bg-gray-50 transition-colors group">
                        <div  class="flex gap-4">
                            <!-- Número da Questão -->
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center">
                                    {{ index + 1 }}
                                </div>
                            </div>

                            <!-- Conteúdo da Questão -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="font-medium text-gray-900 leading-relaxed flex-1 pr-4">
                                        {{ q.statement }}
                                    </h4>
                                    <div class="flex items-center gap-3">
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full whitespace-nowrap">
                                            {{ q.pivot.points_override ?? q.points }} pontos
                                        </span>
                                        <Link :href="route('questions.edit', q.id)"
                                            class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 hover:underline transition-colors whitespace-nowrap">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Editar
                                        </Link>
                                    </div>
                                </div>

                                <!-- Metadados -->
                                <div class="flex flex-wrap items-center gap-4 mt-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full" 
                                              :style="{ backgroundColor: q.subject?.color || '#6B7280' }"></span>
                                        <span class="text-sm text-gray-600">{{ q.subject?.name || 'Sem matéria' }}</span>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">{{ q.question_type?.name || 'Tipo não definido' }}</span>
                                    </div>

                                    <div class="flex items-center gap-2" v-if="q.topic">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">{{ q.topic.name }}</span>
                                    </div>

                                    <div class="flex items-center gap-2" v-if="q.alternatives && q.alternatives.length > 0">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">{{ q.alternatives.length }} alternativas</span>
                                    </div>
                                </div>

                                <!-- Tags -->
                                <div v-if="q.tags && q.tags.length > 0" class="flex flex-wrap gap-2 mt-3">
                                    <span v-for="tag in q.tags" :key="tag.id"
                                          class="px-2 py-1 text-xs font-medium rounded-md"
                                          :style="{ backgroundColor: tag.color + '20', color: tag.color }">
                                        {{ tag.name }}
                                    </span>
                                </div>

                                <!-- Alternativas (se houver) -->
                                <div v-if="q.alternatives && q.alternatives.length > 0" class="mt-4 space-y-2">
                                    <div v-for="(alt, altIndex) in q.alternatives" 
                                         :key="alt.id"
                                         class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                        <div class="flex-shrink-0 w-6 h-6 rounded border-2 border-gray-300 flex items-center justify-center text-sm font-semibold">
                                            {{ String.fromCharCode(65 + altIndex) }}
                                        </div>
                                        <div class="flex-1 text-sm text-gray-700 leading-relaxed">
                                            {{ alt.content }}
                                        </div>
                                        <div v-if="alt.is_correct" class="flex-shrink-0">
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">
                                                Correta
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações no Rodapé -->
            <div class="mt-8 flex justify-between items-center">
                <Link :href="route('exams.index')"
                      class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar para Provas
                </Link>
                
                <div class="flex gap-3">
                    <button @click="deleteExam"
                            class="inline-flex items-center px-4 py-2.5 text-red-600 hover:bg-red-50 border border-red-200 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Excluir Exame
                    </button>
                    
                    <Link :href="route('exams.edit', exam.id)"
                          class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white hover:bg-blue-700 font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar Exame
                    </Link>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <ExamPreview 
            v-if="showPreview"
            :show="showPreview"
            :exam="exam"
            :questions="exam.questions"
            :question-types="questionTypes"
            @close="showPreview = false"
            @edit="$inertia.visit(route('exams.edit', exam.id))"
            @export-pdf="exportPDF"
            @export-docx="exportDOCX"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ExamPreview from '@/Components/Exams/ExamPreview.vue';

const props = defineProps({
    exam: Object,
    questionTypes: {
        type: Array,
        default: () => []
    }
});

// Estados
const showPreview = ref(false);
const showExportMenu = ref(false);

// Computed
const subjectsCount = computed(() => {
    const subjects = props.exam.questions.map(q => q.subject?.id).filter(id => id);
    return new Set(subjects).size;
});

// Métodos
const formatDate = (date) => {
    if (!date) return 'Data não definida';
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const openPreview = () => {
    showPreview.value = true;
};

const toggleExportMenu = () => {
    showExportMenu.value = !showExportMenu.value;
};

const exportPDF = (withAnswers = false) => {
    const url = `/exams/${props.exam.id}/export-pdf?with_answers=${withAnswers}`;
    window.open(url, '_blank');
    showExportMenu.value = false;
};

const exportDOCX = (withAnswers = false) => {
    const url = `/exams/${props.exam.id}/export-docx?with_answers=${withAnswers}`;
    window.open(url, '_blank');
    showExportMenu.value = false;
};

const deleteExam = () => {
    if (confirm(`Tem certeza que deseja excluir a prova "${props.exam.title}"?\n\nEsta ação não pode ser desfeita.`)) {
        router.delete(route('exams.destroy', props.exam.id));
    }
};

// Fechar menu de exportação ao clicar fora — precisa ser removido no unmount,
// senão o listener se acumula a cada visita SPA a esta página.
const closeExportMenuOnOutsideClick = (e) => {
    if (!e.target.closest('.relative.group')) {
        showExportMenu.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeExportMenuOnOutsideClick));
onBeforeUnmount(() => document.removeEventListener('click', closeExportMenuOnOutsideClick));
</script>

<style scoped>
.line-clamp-2 {
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.line-clamp-3 {
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>