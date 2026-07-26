<!--
    Estado "vazio" único. Hoje existem 4 tratamentos visuais distintos para a
    mesma situação (.empty-state-elegant em Questões, marcação própria em
    Documentos, em Provas e no ExamBuilder) — este componente é o candidato
    único para as três listagens.
-->
<template>
    <div class="text-center py-12 px-4">
        <div v-if="$slots.icon" class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
            <slot name="icon" />
        </div>
        <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
        <p v-if="description" class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">{{ description }}</p>
        <div v-if="$slots.actions" class="mt-6 flex items-center justify-center gap-3">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
});
</script>
