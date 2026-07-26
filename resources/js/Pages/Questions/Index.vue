<template>
    <AppLayout>
        <Head title="Questões" />

        <div class="space-y-6">
            <PageHeader title="Questões" subtitle="Gerencie seu banco de questões de forma inteligente">
                <template #actions>
                    <BaseButton variant="outline" @click="showFilters = !showFilters">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filtros
                    </BaseButton>
                    <BaseButton :href="route('questions.create')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nova Questão
                    </BaseButton>
                </template>
            </PageHeader>

            <!-- Painel de filtros -->
            <div v-if="showFilters" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <FormField label="Buscar" v-slot="{ id }">
                        <input :id="id" v-model="form.search" type="text" placeholder="Digite para buscar..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </FormField>

                    <FormField label="Matéria" v-slot="{ id }">
                        <select :id="id" v-model="form.subject_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas as matérias</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                {{ subject.name }}
                            </option>
                        </select>
                    </FormField>

                    <FormField label="Dificuldade" v-slot="{ id }">
                        <select :id="id" v-model="form.difficulty_level"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas as dificuldades</option>
                            <option v-for="level in difficulty_levels" :key="level.value" :value="level.value">
                                {{ level.label }}
                            </option>
                        </select>
                    </FormField>

                    <FormField label="Status" v-slot="{ id }">
                        <select :id="id" v-model="form.is_active"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos os status</option>
                            <option value="1">Ativas</option>
                            <option value="0">Inativas</option>
                        </select>
                    </FormField>

                    <div class="flex items-end gap-2">
                        <BaseButton class="flex-1" @click="applyFilters">Aplicar Filtros</BaseButton>
                        <BaseButton variant="secondary" icon-only aria-label="Limpar filtros" @click="resetFilters">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </BaseButton>
                    </div>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Total</div>
                    <div class="text-3xl font-bold text-gray-900">{{ stats.total }}</div>
                    <div class="text-xs text-gray-400">questões no banco</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Ativas</div>
                    <div class="text-3xl font-bold text-green-600">{{ stats.active }}</div>
                    <div class="text-xs text-gray-400">prontas para uso</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Inativas</div>
                    <div class="text-3xl font-bold text-red-600">{{ stats.inactive }}</div>
                    <div class="text-xs text-gray-400">aguardando revisão</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Dificuldade</div>
                    <div class="text-3xl font-bold text-blue-600">{{ calculateAverageDifficulty() }}</div>
                    <div class="text-xs text-gray-400">média do banco</div>
                </div>
            </div>

            <!-- Lista de questões -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Lista de Questões</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Mostrando {{ questions.data.length }} de {{ questions.total }} questões
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <label :for="perPageId" class="text-sm text-gray-500">Mostrar:</label>
                        <select :id="perPageId" v-model="form.per_page" @change="applyFilters"
                                class="text-sm py-1 px-2 border border-gray-300 rounded-md">
                            <option value="15">15</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <!-- Ações em lote -->
                <div v-if="selectedIds.length > 0" class="flex items-center justify-between gap-4 border-b border-blue-100 bg-blue-50 px-6 py-3">
                    <p class="text-sm font-medium text-blue-800">{{ selectedIds.length }} questão(ões) selecionada(s)</p>
                    <div class="flex items-center gap-2">
                        <BaseButton size="sm" variant="secondary" @click="reactivateSelected">Reativar selecionadas</BaseButton>
                        <BaseButton size="sm" variant="danger" @click="confirmBulkArchive">Arquivar selecionadas</BaseButton>
                    </div>
                </div>

                <DataTable
                    :columns="columns"
                    :rows="questions.data"
                    :loading="isLoading"
                    selectable
                    :selected="selectedIds"
                    @update:selected="selectedIds = $event"
                    :sort-key="form.sort"
                    :sort-direction="form.direction"
                    @sort="handleSort"
                >
                    <template #cell-statement="{ row }">
                        <div class="max-w-xs">
                            <div class="text-sm font-medium text-gray-900 truncate hover:text-clip hover:whitespace-normal cursor-pointer group">
                                {{ row.statement }}
                                <div class="text-xs text-gray-500 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    {{ row.alternatives_count }} alternativa{{ row.alternatives_count !== 1 ? 's' : '' }}
                                </div>
                            </div>
                            <div class="text-xs text-gray-400 mt-1 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ formatDate(row.created_at) }}
                            </div>
                        </div>
                    </template>

                    <template #cell-subject="{ row }">
                        <div v-if="row.subject" class="flex items-center">
                            <div class="h-8 w-8 rounded-full mr-3 flex items-center justify-center text-white font-medium text-xs shadow-sm"
                                 :style="{ backgroundColor: row.subject.color }">
                                {{ row.subject.name.charAt(0) }}
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-900">{{ row.subject.name }}</span>
                                <div v-if="row.topic" class="text-xs text-gray-500">{{ row.topic.name }}</div>
                            </div>
                        </div>
                        <span v-else class="text-sm text-gray-500">Sem matéria</span>
                    </template>

                    <template #cell-difficulty_level="{ row }">
                        <DifficultyBadge :level="row.difficulty_level" />
                    </template>

                    <template #cell-points="{ row }">
                        <span class="text-lg font-bold text-gray-900">{{ row.points }}</span>
                        <span class="text-sm text-gray-500">ponto{{ row.points !== 1 ? 's' : '' }}</span>
                    </template>

                    <template #cell-is_active="{ row }">
                        <StatusBadge :label="row.is_active ? 'Ativa' : 'Inativa'" :tone="row.is_active ? 'green' : 'red'" />
                    </template>

                    <template #cell-created_at="{ row }">
                        <span class="text-sm text-gray-500 whitespace-nowrap">{{ formatDate(row.created_at) }}</span>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-2">
                            <BaseButton :href="route('questions.show', row.id)" variant="outline" size="sm" icon-only :aria-label="`Visualizar questão: ${row.statement.substring(0, 40)}`">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton :href="route('questions.edit', row.id)" variant="outline" size="sm" icon-only :aria-label="`Editar questão: ${row.statement.substring(0, 40)}`">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton v-if="row.is_active" variant="danger-outline" size="sm" icon-only
                                        :aria-label="`Arquivar questão: ${row.statement.substring(0, 40)}`"
                                        @click="confirmArchive(row)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M9 8V6a2 2 0 012-2h2a2 2 0 012 2v2m2 0v10a2 2 0 01-2 2H9a2 2 0 01-2-2V8h10z"/>
                                </svg>
                            </BaseButton>
                            <BaseButton v-else variant="outline" size="sm" icon-only
                                        :aria-label="`Reativar questão: ${row.statement.substring(0, 40)}`"
                                        class="!text-green-600 !border-green-300 hover:!bg-green-50"
                                        @click="reactivateQuestion(row)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </BaseButton>
                        </div>
                    </template>

                    <template #empty>
                        <EmptyState title="Nenhuma questão encontrada"
                                    :description="hasFilters ? 'Não encontramos questões com os filtros aplicados.' : 'Você ainda não criou nenhuma questão. Comece criando sua primeira!'">
                            <template #icon>
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            <template #actions>
                                <BaseButton v-if="hasFilters" variant="secondary" @click="resetFilters">Limpar Filtros</BaseButton>
                                <BaseButton :href="route('questions.create')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Criar Primeira Questão
                                </BaseButton>
                            </template>
                        </EmptyState>
                    </template>
                </DataTable>

                <div v-if="questions.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <Pagination :links="questions.links"
                                :summary="`Página ${questions.current_page} de ${questions.last_page}`" />
                </div>
            </div>
        </div>

        <ConfirmDialog
            v-model:show="showArchiveConfirm"
            title="Arquivar questão"
            :message="`Tem certeza que deseja arquivar ${archiveLabel}? Ela deixa de aparecer no banco de questões e na montagem de provas, mas pode ser reativada depois.`"
            confirm-text="Sim, arquivar"
            cancel-text="Cancelar"
            type="warning"
            @confirm="archiveQuestions"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, useId } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormField from '@/Components/UI/FormField.vue';
import DifficultyBadge from '@/Components/UI/DifficultyBadge.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    questions: Object,
    filters: Object,
    subjects: Array,
    topics: Array,
    question_types: Array,
    tags: Array,
    difficulty_levels: Array,
    stats: Object,
});

const columns = [
    { key: 'statement', label: 'Enunciado', sortable: true },
    { key: 'subject', label: 'Matéria' },
    { key: 'difficulty_level', label: 'Dificuldade', sortable: true },
    { key: 'points', label: 'Pontos', sortable: true, align: 'center' },
    { key: 'is_active', label: 'Status', sortable: true },
    { key: 'created_at', label: 'Criada em', sortable: true },
    { key: 'actions', label: 'Ações', align: 'right' },
];

// Estado
const showFilters = ref(false);
const selectedIds = ref([]);
const isLoading = ref(false);
const perPageId = `per-page-${useId()}`;

const showArchiveConfirm = ref(false);
const archiveIds = ref([]);
const archiveLabel = ref('');

// Formulário de filtros
const form = useForm({
    search: props.filters.search || '',
    subject_id: props.filters.subject_id || '',
    topic_id: props.filters.topic_id || '',
    difficulty_level: props.filters.difficulty_level || '',
    question_type_id: props.filters.question_type_id || '',
    is_active: props.filters.is_active || '',
    per_page: props.filters.per_page || 15,
    sort: props.filters.sort || '',
    direction: props.filters.direction || 'asc',
});

// Computed — `per_page`/`sort`/`direction` sempre vêm preenchidos, então não
// contam como "filtro aplicado" para efeito da mensagem de estado vazio.
const hasFilters = computed(() => {
    const ignored = ['per_page', 'sort', 'direction'];
    return Object.entries(props.filters).some(
        ([key, value]) => !ignored.includes(key) && value !== '' && value !== null && value !== undefined,
    );
});

// Métodos
const applyFilters = () => {
    form.get(route('questions.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    form.reset();
    applyFilters();
};

const handleSort = ({ key, direction }) => {
    form.sort = key;
    form.direction = direction;
    applyFilters();
};

const confirmArchive = (question) => {
    archiveIds.value = [question.id];
    const excerpt = question.statement.length > 60 ? `${question.statement.substring(0, 60)}…` : question.statement;
    archiveLabel.value = `a questão "${excerpt}"`;
    showArchiveConfirm.value = true;
};

const confirmBulkArchive = () => {
    archiveIds.value = [...selectedIds.value];
    archiveLabel.value = `${selectedIds.value.length} questão(ões) selecionada(s)`;
    showArchiveConfirm.value = true;
};

const archiveQuestions = () => {
    router.patch(route('questions.bulk-status'), { ids: archiveIds.value, is_active: false }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selectedIds.value = selectedIds.value.filter((id) => !archiveIds.value.includes(id));
            showArchiveConfirm.value = false;
        },
    });
};

const reactivateQuestion = (question) => {
    router.patch(route('questions.bulk-status'), { ids: [question.id], is_active: true }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const reactivateSelected = () => {
    const ids = [...selectedIds.value];
    router.patch(route('questions.bulk-status'), { ids, is_active: true }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id));
        },
    });
};

const calculateAverageDifficulty = () => {
    const weights = { easy: 1, medium: 2, hard: 3 };
    const total = props.stats.by_difficulty.easy + props.stats.by_difficulty.medium + props.stats.by_difficulty.hard;

    if (total === 0) return 'N/A';

    const average = (
        (props.stats.by_difficulty.easy * weights.easy +
         props.stats.by_difficulty.medium * weights.medium +
         props.stats.by_difficulty.hard * weights.hard) / total
    ).toFixed(1);

    return average <= 1.5 ? 'Fácil' : average <= 2.5 ? 'Média' : 'Difícil';
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
};

// Watch para busca com debounce
watch(
    () => form.search,
    debounce(() => {
        if (form.search !== undefined) {
            applyFilters();
        }
    }, 500)
);

// Estado de carregamento durante navegações Inertia (filtro, sort, paginação)
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
