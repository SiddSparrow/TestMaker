<!--
    Ações de prova (Visualizar/Editar/Exportar/Excluir) reunidas num só
    componente — Exams/Show.vue repetia "Editar Prova" duas vezes (cabeçalho
    e rodapé, com estilos diferentes) e Exams/Edit.vue duplicava o botão
    "Excluir Prova" com a mesma marcação. `on-gradient` alterna entre o
    tratamento translúcido usado dentro de DetailHeader (fundo em gradiente)
    e o tratamento sólido usado em áreas com fundo branco.
-->
<template>
    <div class="flex flex-wrap items-center gap-3">
        <button v-if="showPreview" type="button" @click="$emit('preview')" :class="btnClass('preview')">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            Visualizar Prova
        </button>

        <Link v-if="showEdit" :href="route('exams.edit', exam.id)" :class="btnClass('edit')">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Editar Prova
        </Link>

        <div v-if="showExport" class="relative" ref="exportMenuRef" @keydown.escape="closeExportMenuAndRefocus">
            <button type="button" @click="toggleExportMenu"
                    ref="exportMenuTriggerRef"
                    aria-haspopup="true"
                    :aria-expanded="showExportMenu"
                    :class="btnClass('export')">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportar
            </button>

            <div v-show="showExportMenu"
                 role="menu"
                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-10">
                <div class="py-1">
                    <button @click="handleExport('pdf', false)" role="menuitem" type="button"
                            class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4 mr-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                        </svg>
                        Exportar PDF
                    </button>
                    <button @click="handleExport('pdf', true)" role="menuitem" type="button"
                            class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4 mr-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                        </svg>
                        PDF com Gabarito
                    </button>
                    <button @click="handleExport('docx', false)" role="menuitem" type="button"
                            class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4 mr-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                        </svg>
                        Exportar DOCX
                    </button>
                    <button @click="handleExport('docx', true)" role="menuitem" type="button"
                            class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4 mr-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                        </svg>
                        DOCX com Gabarito
                    </button>
                </div>
            </div>
        </div>

        <button v-if="showDelete" type="button" @click="$emit('delete')" :class="btnClass('delete')">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Excluir Prova
        </button>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useFileDownload } from '@/composables/useFileDownload';

const props = defineProps({
    exam: { type: Object, required: true },
    showPreview: { type: Boolean, default: true },
    showEdit: { type: Boolean, default: true },
    showExport: { type: Boolean, default: true },
    showDelete: { type: Boolean, default: false },
    // Fundo translúcido para uso dentro de DetailHeader (gradiente); fora
    // dele, o tratamento sólido é o legível.
    onGradient: { type: Boolean, default: false },
});

defineEmits(['preview', 'delete']);

const { download } = useFileDownload();

const showExportMenu = ref(false);
const exportMenuRef = ref(null);
const exportMenuTriggerRef = ref(null);

const toggleExportMenu = () => {
    showExportMenu.value = !showExportMenu.value;
};

const handleExport = (format, withAnswers) => {
    const routeName = format === 'pdf' ? 'exams.export.pdf' : 'exams.export.docx';
    download(route(routeName, { exam: props.exam.id, with_answers: withAnswers ? 1 : 0 }));
    showExportMenu.value = false;
};

// Fechar menu de exportação ao clicar fora — precisa ser removido no
// unmount, senão o listener se acumula a cada visita SPA a esta página.
const closeExportMenuOnOutsideClick = (e) => {
    if (exportMenuRef.value && !exportMenuRef.value.contains(e.target)) {
        showExportMenu.value = false;
    }
};

const closeExportMenuAndRefocus = () => {
    showExportMenu.value = false;
    exportMenuTriggerRef.value?.focus();
};

onMounted(() => document.addEventListener('click', closeExportMenuOnOutsideClick));
onBeforeUnmount(() => document.removeEventListener('click', closeExportMenuOnOutsideClick));

const gradientClasses = {
    preview: 'inline-flex items-center justify-center px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-all border border-white/30 hover:border-white/50',
    edit: 'inline-flex items-center justify-center px-5 py-2.5 bg-white text-blue-600 hover:bg-gray-50 font-medium rounded-lg transition-all border border-white',
    export: 'inline-flex items-center justify-center px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-lg transition-all',
    delete: 'inline-flex items-center justify-center px-5 py-2.5 bg-white/20 hover:bg-red-500 text-white font-medium rounded-lg transition-all border border-white/30',
};

const solidClasses = {
    preview: 'inline-flex items-center px-5 py-2.5 bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 font-medium rounded-lg transition-colors',
    edit: 'inline-flex items-center px-5 py-2.5 bg-blue-600 text-white hover:bg-blue-700 font-medium rounded-lg transition-colors',
    export: 'inline-flex items-center px-5 py-2.5 bg-emerald-600 text-white hover:bg-emerald-700 font-medium rounded-lg transition-colors',
    delete: 'inline-flex items-center px-4 py-2.5 text-red-600 hover:bg-red-50 border border-red-200 rounded-lg transition-colors',
};

const btnClass = (action) => (props.onGradient ? gradientClasses[action] : solidClasses[action]);
</script>
