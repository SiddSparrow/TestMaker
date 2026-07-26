<!--
    Tabela padrão para as três listagens (Questões, Provas, Documentos), que
    hoje têm três marcações diferentes (.table-elegant, Tailwind cru em duas
    variações), nenhuma permite ordenar por coluna ou selecionar em lote, e
    nenhuma mostra progresso ao filtrar/paginar — a tela só "congela".

    Uso:
      <DataTable
        :columns="[{ key: 'statement', label: 'Enunciado', sortable: true }, { key: 'actions', label: 'Ações', align: 'right' }]"
        :rows="questions.data"
        :loading="isFiltering"
        v-model:selected="selectedIds"
        selectable
        @sort="applySort"
      >
        <template #cell-statement="{ row }">{{ row.statement }}</template>
        <template #cell-actions="{ row }">...</template>
        <template #empty>...</template>
      </DataTable>
-->
<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th v-if="selectable" scope="col" class="w-10 px-4 py-3">
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            aria-label="Selecionar todas as linhas"
                            :checked="allSelected"
                            :indeterminate="someSelected && !allSelected"
                            @change="toggleAll"
                        />
                    </th>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        scope="col"
                        class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider"
                        :class="[alignClass(col.align), col.headerClass]"
                        :aria-sort="ariaSortFor(col)"
                    >
                        <button
                            v-if="col.sortable"
                            type="button"
                            class="inline-flex items-center gap-1 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded"
                            @click="toggleSort(col.key)"
                        >
                            {{ col.label }}
                            <svg
                                class="w-3 h-3 transition-transform"
                                :class="{ 'rotate-180': sortKey === col.key && sortDirection === 'desc', 'opacity-30': sortKey !== col.key }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                        </button>
                        <span v-else>{{ col.label }}</span>
                    </th>
                </tr>
            </thead>

            <tbody v-if="loading" class="bg-white divide-y divide-gray-200">
                <tr v-for="n in skeletonRows" :key="n">
                    <td v-if="selectable" class="px-4 py-4"></td>
                    <td v-for="col in columns" :key="col.key" class="px-6 py-4">
                        <Skeleton class="h-4" />
                    </td>
                </tr>
            </tbody>

            <tbody v-else-if="rows.length === 0">
                <tr>
                    <td :colspan="totalColumns" class="px-6 py-4">
                        <slot name="empty" />
                    </td>
                </tr>
            </tbody>

            <tbody v-else class="bg-white divide-y divide-gray-200">
                <tr v-for="row in rows" :key="row[rowKey]" class="hover:bg-gray-50 transition-colors">
                    <td v-if="selectable" class="px-4 py-4">
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            :aria-label="`Selecionar linha ${row[rowKey]}`"
                            :checked="selected.includes(row[rowKey])"
                            @change="toggleRow(row[rowKey])"
                        />
                    </td>
                    <td
                        v-for="col in columns"
                        :key="col.key"
                        class="px-6 py-4"
                        :class="[alignClass(col.align), col.cellClass]"
                    >
                        <slot :name="`cell-${col.key}`" :row="row">{{ row[col.key] }}</slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Skeleton from '@/Components/UI/Skeleton.vue';

const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, required: true },
    rowKey: { type: String, default: 'id' },
    sortKey: { type: String, default: null },
    sortDirection: { type: String, default: 'asc' },
    selectable: { type: Boolean, default: false },
    selected: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    skeletonRows: { type: Number, default: 5 },
});

const emit = defineEmits(['sort', 'update:selected']);

const totalColumns = computed(() => props.columns.length + (props.selectable ? 1 : 0));

const allSelected = computed(() => props.rows.length > 0 && props.rows.every((row) => props.selected.includes(row[props.rowKey])));
const someSelected = computed(() => props.selected.length > 0);

const alignClass = (align) => ({ right: 'text-right', center: 'text-center' }[align] || 'text-left');

const ariaSortFor = (col) => {
    if (!col.sortable) return undefined;
    if (props.sortKey !== col.key) return 'none';
    return props.sortDirection === 'asc' ? 'ascending' : 'descending';
};

const toggleSort = (key) => {
    const direction = props.sortKey === key && props.sortDirection === 'asc' ? 'desc' : 'asc';
    emit('sort', { key, direction });
};

const toggleRow = (id) => {
    const next = props.selected.includes(id)
        ? props.selected.filter((selectedId) => selectedId !== id)
        : [...props.selected, id];
    emit('update:selected', next);
};

const toggleAll = () => {
    emit('update:selected', allSelected.value ? [] : props.rows.map((row) => row[props.rowKey]));
};
</script>
