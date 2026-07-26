<template>
    <AppLayout>
        <Head title="Provas" />

        <div class="space-y-6">
            <PageHeader title="Provas" subtitle="Gerencie e visualize suas provas criadas">
                <template #actions>
                    <BaseButton :href="route('exams.create')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nova Prova
                    </BaseButton>
                </template>
            </PageHeader>

            <!-- Cards de estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Total de Provas</div>
                    <div class="text-3xl font-bold text-gray-900">{{ stats.total }}</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Próxima Prova</div>
                    <div class="text-2xl font-bold text-gray-900">{{ nextExamLabel }}</div>
                </div>
            </div>

            <!-- Busca -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex items-center gap-2 text-gray-700">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span class="text-sm font-medium">
                            {{ exams.total }} {{ exams.total === 1 ? 'prova encontrada' : 'provas encontradas' }}
                        </span>
                    </div>
                    <div class="relative w-full sm:w-80">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            v-model="form.search"
                            aria-label="Buscar provas por título ou descrição"
                            placeholder="Buscar por título ou descrição..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200 text-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Lista de provas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Lista de Provas</h3>
                </div>

                <DataTable
                    :columns="columns"
                    :rows="exams.data"
                    :loading="isLoading"
                    :sort-key="form.sort"
                    :sort-direction="form.direction"
                    @sort="handleSort"
                >
                    <template #cell-title="{ row }">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-semibold text-gray-900">{{ row.title }}</div>
                                <div class="text-xs text-gray-500 truncate max-w-xs">{{ row.description || 'Sem descrição' }}</div>
                            </div>
                        </div>
                    </template>

                    <template #cell-exam_date="{ row }">
                        <div class="text-sm text-gray-900">{{ formatDate(row.exam_date) }}</div>
                        <div class="text-xs text-gray-500">{{ getDaysFromNow(row.exam_date) }}</div>
                    </template>

                    <template #cell-questions_count="{ row }">
                        <StatusBadge :label="`${row.questions_count || 0} questões`" tone="blue" />
                    </template>

                    <template #cell-status="{ row }">
                        <div class="flex flex-col items-start gap-1">
                            <StatusBadge :label="getExamStatus(row)" :tone="getStatusTone(row)" dot />
                            <StatusBadge :label="row.is_published ? 'Publicada' : 'Rascunho'" :tone="row.is_published ? 'green' : 'gray'" />
                        </div>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-1">
                            <BaseButton variant="outline" size="sm" icon-only :aria-label="`Visualizar prova: ${row.title}`" @click="openPreview(row)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton :href="route('exams.edit', row.id)" variant="outline" size="sm" icon-only :aria-label="`Editar prova: ${row.title}`">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton variant="outline" size="sm" icon-only :aria-label="`Duplicar prova: ${row.title}`" @click="duplicateExam(row)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 4h8a2 2 0 012 2v8a2 2 0 01-2 2h-8a2 2 0 01-2-2v-8a2 2 0 012-2z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton variant="outline" size="sm" icon-only
                                        :aria-label="row.is_published ? `Despublicar prova: ${row.title}` : `Publicar prova: ${row.title}`"
                                        @click="togglePublish(row)">
                                <svg v-if="row.is_published" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.411m3.087-2.87A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.965 9.965 0 01-4.132 5.411M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton variant="danger-outline" size="sm" icon-only :aria-label="`Excluir prova: ${row.title}`"
                                        @click="confirmDelete(row)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </BaseButton>
                        </div>
                    </template>

                    <template #empty>
                        <EmptyState :title="form.search ? 'Nenhuma prova encontrada' : 'Nenhuma prova criada'"
                                    :description="form.search ? 'Tente ajustar sua busca ou limpe o filtro.' : 'Comece criando sua primeira prova!'">
                            <template #icon>
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </template>
                            <template #actions>
                                <BaseButton v-if="form.search" variant="secondary" @click="form.search = ''">Limpar busca</BaseButton>
                                <BaseButton v-else :href="route('exams.create')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Criar Primeira Prova
                                </BaseButton>
                            </template>
                        </EmptyState>
                    </template>
                </DataTable>

                <div v-if="exams.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <Pagination :links="exams.links" :summary="`Página ${exams.current_page} de ${exams.last_page}`" />
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

        <ConfirmDialog
            v-model:show="showDeleteConfirm"
            title="Excluir prova"
            :message="deleteMessage"
            confirm-text="Sim, excluir"
            cancel-text="Cancelar"
            type="danger"
            @confirm="deleteExam"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import ExamPreview from '@/Components/Exams/ExamPreview.vue';
import { useToast } from '@/composables/useToast';
import { useFileDownload } from '@/composables/useFileDownload';

const props = defineProps({
    exams: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({ total: 0, next_exam_date: null }) },
    questionTypes: { type: Array, default: () => [] },
});

const toast = useToast();
const { download } = useFileDownload();

const columns = [
    { key: 'title', label: 'Prova', sortable: true },
    { key: 'exam_date', label: 'Data', sortable: true },
    { key: 'questions_count', label: 'Questões' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Ações', align: 'right' },
];

const showPreview = ref(false);
const selectedExam = ref(null);
const examQuestions = ref([]);
const isLoading = ref(false);

const showDeleteConfirm = ref(false);
const examToDelete = ref(null);

const form = useForm({
    search: props.filters.search || '',
    sort: props.filters.sort || '',
    direction: props.filters.direction || 'desc',
    per_page: props.filters.per_page || 15,
});

const nextExamLabel = computed(() => (props.stats.next_exam_date ? formatDate(props.stats.next_exam_date) : 'Nenhuma'));

const examData = computed(() => {
    if (!selectedExam.value) return {};

    return {
        title: selectedExam.value.title || '',
        description: selectedExam.value.description || '',
        exam_date: selectedExam.value.exam_date || null,
        header_config: selectedExam.value.header_config || {
            school_name: '',
            show_date: true,
            show_student_info: true,
        },
        footer_config: selectedExam.value.footer_config || {
            custom_text: 'Boa prova!',
            show_page_number: true,
        },
        total_points: selectedExam.value.total_points ||
            examQuestions.value.reduce((sum, q) => sum + (q.points_override || q.points || 0), 0),
    };
});

const deleteMessage = computed(() => {
    const title = examToDelete.value?.title || '';
    return `Tem certeza que deseja excluir a prova "${title}"? Isso remove os vínculos com as questões e apaga a prova definitivamente do banco de dados — não é possível desfazer.`;
});

// Métodos
const applyFilters = () => {
    form.get(route('exams.index'), { preserveState: true, preserveScroll: true });
};

const handleSort = ({ key, direction }) => {
    form.sort = key;
    form.direction = direction;
    applyFilters();
};

const formatDate = (dateString) => {
    if (!dateString) return 'Não definida';
    return new Date(dateString).toLocaleDateString('pt-BR');
};

const getDaysFromNow = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const today = new Date();
    const diffDays = Math.ceil((date - today) / (1000 * 60 * 60 * 24));

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

const getStatusTone = (exam) => {
    const status = getExamStatus(exam);
    if (status === 'Agendada') return 'green';
    if (status === 'Hoje') return 'blue';
    return 'gray';
};

const openPreview = async (exam) => {
    selectedExam.value = exam;
    showPreview.value = true;
    await fetchExamQuestions(exam.id);
};

const fetchExamQuestions = async (examId) => {
    try {
        const response = await window.axios.get(route('exams.questions', examId));
        examQuestions.value = response.data.questions || [];
    } catch {
        examQuestions.value = [];
        toast.error('Não foi possível carregar as questões desta prova.');
    }
};

const duplicateExam = (exam) => {
    router.post(route('exams.duplicate', exam.id));
};

const togglePublish = (exam) => {
    router.post(route('exams.toggle-publish', exam.id), {}, { preserveScroll: true, preserveState: true });
};

const confirmDelete = (exam) => {
    examToDelete.value = exam;
    showDeleteConfirm.value = true;
};

const deleteExam = () => {
    if (!examToDelete.value) return;

    router.delete(route('exams.destroy', examToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            examToDelete.value = null;
        },
        onError: () => {
            toast.error('Erro ao excluir a prova. Tente novamente.');
        },
    });
};

const exportPDF = (withAnswers = false) => {
    if (!selectedExam.value) return;
    download(route('exams.export.pdf', { exam: selectedExam.value.id, with_answers: withAnswers ? 1 : 0 }));
};

const exportDOCX = (withAnswers = false) => {
    if (!selectedExam.value) return;
    download(route('exams.export.docx', { exam: selectedExam.value.id, with_answers: withAnswers ? 1 : 0 }));
};

// Busca com debounce
watch(() => form.search, debounce(applyFilters, 500));

// Estado de carregamento durante navegações Inertia (busca, ordenação, paginação)
let stopStart;
let stopFinish;

// Só liga quando o destino da navegação é esta mesma rota — sem isso,
// sair da tela por qualquer link fazia a tabela piscar em skeleton.
onMounted(() => {
    stopStart = router.on('start', (event) => {
        if (event.detail.visit.url.pathname === window.location.pathname) {
            isLoading.value = true;
        }
    });
    stopFinish = router.on('finish', () => { isLoading.value = false; });
});

onBeforeUnmount(() => {
    stopStart?.();
    stopFinish?.();
});
</script>
