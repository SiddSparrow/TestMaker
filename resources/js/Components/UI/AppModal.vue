<!--
    Modal padrão. Envolve o Modal.vue existente, que já usa <dialog> +
    showModal() — o navegador já trata Escape, foco preso e trava o scroll
    do fundo nativamente. Hoje só Modal.vue e Dropdown.vue têm isso; os
    outros seis modais do app (SubjectsModal, TopicsModal, TagsModal,
    ExamPreview, ExamConfigEditModal, o modal ad-hoc de Questions/Index) são
    `<div>` fixos sem nada disso. Este componente passa a ser a base única.
-->
<template>
    <Modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="$emit('close')">
        <div class="p-6">
            <div v-if="title || closeable" class="flex items-start justify-between mb-4">
                <h2 v-if="title" class="text-lg font-semibold text-gray-900">{{ title }}</h2>
                <button
                    v-if="closeable"
                    type="button"
                    aria-label="Fechar"
                    class="ml-auto rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                    @click="$emit('close')"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <slot />
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal.vue';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: null },
    maxWidth: { type: String, default: '2xl' },
    closeable: { type: Boolean, default: true },
});

defineEmits(['close']);
</script>
