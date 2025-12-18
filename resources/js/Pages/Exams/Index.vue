<template>
    <AppLayout>
            <div class="space-y-6">
                <!-- Header com botão -->
                <div class="flex justify-between items-center ml-mr-1 slide-up" style="animation-delay: 100ms">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Minhas Provas</h1>
                    <p class="text-sm text-gray-600 mt-1">Gerencie e visualize suas provas criadas</p>
                </div>
                <button 
                    @click="router.visit(route('exams.create'))"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nova Prova
                </button>
            </div>

            <!-- Cards de estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 ml-mr-1 slide-up" style="animation-delay: 150ms">
                <StatCard 
                    title="Total de Provas"
                    :value="exams.length"
                    icon="DocumentTextIcon"
                    color="blue"
                />
                <StatCard 
                    title="Próxima Prova"
                    :value="nextExamDate || 'Nenhuma'"
                    icon="CalendarIcon"
                    color="green"
                    :is-text="true"
                />
            </div>

            <!-- Filtros e busca -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 ml-mr-1 slide-up" style="animation-delay: 200ms">
                <div class="px-6 py-4">
                    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div class="flex items-center space-x-2 text-gray-700">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span class="text-sm font-medium">{{ filteredExams.length }} {{ filteredExams.length === 1 ? 'prova encontrada' : 'provas encontradas' }}</span>
                        </div>
                        
                        <div class="relative w-full sm:w-80">
                            <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input 
                                type="text" 
                                v-model="searchQuery"
                                placeholder="Buscar por título ou descrição..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200 text-sm"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de provas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 ml-mr-1 slide-up" style="animation-delay: 250ms">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Lista de Provas</h3>
                </div>

                <!-- Tabela -->
                <div class="overflow-x-auto" v-if="filteredExams.length > 0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Prova
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Questões
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr 
                                v-for="exam in filteredExams" 
                                :key="exam.id" 
                                class="hover:bg-gray-50 transition-colors duration-150"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900">{{ exam.title }}</div>
                                            <div class="text-xs text-gray-500 truncate max-w-xs">{{ exam.description || 'Sem descrição' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ formatDate(exam.exam_date) }}</div>
                                    <div class="text-xs text-gray-500">{{ getDaysFromNow(exam.exam_date) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ exam.questions_count || 0 }} questões
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        getStatusColor(exam)
                                    ]">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getStatusDotColor(exam)"></span>
                                        {{ getExamStatus(exam) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button
                                            @click="openPreview(exam)"
                                            class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50 transition-colors"
                                            title="Visualizar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                        
                                        <button
                                            @click="router.visit(route('exams.edit', exam.id))"
                                            class="text-gray-600 hover:text-gray-900 p-1 rounded hover:bg-gray-50 transition-colors"
                                            title="Editar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        
                                        <button
                                            @click="deleteExam(exam)"
                                            class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors"
                                            title="Excluir"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="p-12 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        {{ searchQuery ? 'Nenhuma prova encontrada' : 'Nenhuma prova criada' }}
                    </h3>
                    <p class="text-gray-500 mb-6">
                        {{ searchQuery 
                            ? 'Tente ajustar sua busca ou limpe os filtros.' 
                            : 'Comece criando sua primeira prova!' 
                        }}
                    </p>
                    <button 
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors duration-200"
                    >
                        Limpar busca
                    </button>
                    <button 
                        v-else
                        @click="router.visit(route('exams.create'))"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors duration-200"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Criar Primeira Prova
                    </button>
                </div>
            </div>
            
            <!-- Preview Modal -->
            <ExamPreview
                v-if="showPreview"
                :show="showPreview"
                :exam="examData"
                :questions="examQuestions"
                :question-types="questionTypes"
                @close="showPreview = false"
                @export-pdf="exportPDF"
                @export-docx="exportDOCX"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import StatCard from '@/Components/StatCard.vue';
import ExamPreview from '@/Components/Exams/ExamPreview.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    exams: {
        type: Array,
        default: () => []
    },
    questionTypes: {
        type: Array,
        default: () => []
    }
});

const showPreview = ref(false);
const selectedExam = ref(null);
const examQuestions = ref([]);
const searchQuery = ref('');

// Computed Properties
const nextExamDate = computed(() => {
    const futureExams = props.exams.filter(exam => {
        if (!exam.exam_date) return false;
        return new Date(exam.exam_date) > new Date();
    });
    
    if (futureExams.length === 0) return null;
    
    const nextExam = futureExams.sort((a, b) => 
        new Date(a.exam_date) - new Date(b.exam_date)
    )[0];
    
    return formatDate(nextExam.exam_date);
});

const filteredExams = computed(() => {
    let filtered = [...props.exams];
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(exam => 
            exam.title.toLowerCase().includes(query) ||
            (exam.description && exam.description.toLowerCase().includes(query))
        );
    }
    
    // Sort by date (most recent first)
    filtered.sort((a, b) => {
        const dateA = a.exam_date ? new Date(a.exam_date) : new Date(0);
        const dateB = b.exam_date ? new Date(b.exam_date) : new Date(0);
        return dateB - dateA;
    });
    
    return filtered;
});

const examData = computed(() => {
    if (!selectedExam.value) return {};
    
    return {
        title: selectedExam.value.title || '',
        description: selectedExam.value.description || '',
        exam_date: selectedExam.value.exam_date || null,
        header_config: selectedExam.value.header_config || {
            school_name: '',
            show_date: true,
            show_student_info: true
        },
        footer_config: selectedExam.value.footer_config || {
            custom_text: 'Boa prova!',
            show_page_number: true
        },
        total_points: selectedExam.value.total_points || 
            examQuestions.value.reduce((sum, q) => {
                return sum + (q.points_override || q.points || 0);
            }, 0)
    };
});

// Methods
const formatDate = (dateString) => {
    if (!dateString) return 'Não definida';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
};

const getDaysFromNow = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const today = new Date();
    const diffTime = date - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) return 'Hoje';
    if (diffDays === 1) return 'Amanhã';
    if (diffDays === -1) return 'Ontem';
    if (diffDays > 0) return `Em ${diffDays} dias`;
    return `Há ${Math.abs(diffDays)} dias`;
};

const getExamStatus = (exam) => {
    if (!exam.exam_date) return 'Sem data';
    
    const examDate = new Date(exam.exam_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    examDate.setHours(0, 0, 0, 0);
    
    if (examDate > today) return 'Agendada';
    if (examDate.getTime() === today.getTime()) return 'Hoje';
    return 'Realizada';
};

const getStatusColor = (exam) => {
    const status = getExamStatus(exam);
    if (status === 'Agendada') return 'bg-green-100 text-green-800';
    if (status === 'Hoje') return 'bg-blue-100 text-blue-800';
    if (status === 'Realizada') return 'bg-gray-100 text-gray-800';
    return 'bg-gray-100 text-gray-800';
};

const getStatusDotColor = (exam) => {
    const status = getExamStatus(exam);
    if (status === 'Agendada') return 'bg-green-500';
    if (status === 'Hoje') return 'bg-blue-500 animate-pulse';
    return 'bg-gray-400';
};

const openPreview = async (exam) => {
    selectedExam.value = exam;
    showPreview.value = true;
    
    if (exam.questions && Array.isArray(exam.questions)) {
        examQuestions.value = exam.questions;
    } else {
        await fetchExamQuestions(exam.id);
    }
};

const fetchExamQuestions = async (examId) => {
    try {
        const response = await router.get(route('exams.questions', examId));
        examQuestions.value = response.props.questions || [];
    } catch (error) {
        console.error('Erro ao buscar questões:', error);
        examQuestions.value = [];
    }
};

const deleteExam = (exam) => {
    if (confirm(`Tem certeza que deseja excluir a prova "${exam.title}"?\n\nEsta ação não pode ser desfeita.`)) {
        router.delete(route('exams.destroy', exam.id), {
            onSuccess: () => {
                console.log('Prova excluída com sucesso');
            },
            onError: () => {
                alert('Erro ao excluir a prova. Tente novamente.');
            }
        });
    }
};

const exportPDF = (withAnswers = false) => {
    if (!selectedExam.value) return;
    
    const url = route('exams.export.pdf', {
        exam: selectedExam.value.id,
        with_answers: withAnswers ? 1 : 0
    });
    
    // Usa window.location para forçar download
    window.location.href = url;
};

const exportDOCX = (withAnswers = false) => {
    if (!selectedExam.value) return;
    console.log('Exportando DOCX, com respostas:', withAnswers);
    const url = route('exams.export.docx', {
        exam: selectedExam.value.id,
        with_answers: withAnswers ? 1 : 0
    });
    
    // Usa window.location para forçar download
    window.location.href = url;
};
</script>