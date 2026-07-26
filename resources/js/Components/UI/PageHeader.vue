<!--
    Cabeçalho padrão de página de 2º nível: título, subtítulo, breadcrumb e
    ações — reúne num único lugar o que hoje cada tela monta do zero com
    marcação própria (`page-title-elegant`, `text-2xl font-bold`, etc.).
-->
<template>
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <Breadcrumbs v-if="breadcrumb.length" :items="breadcrumb" class="mb-2" />
            <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-gray-600">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="flex items-center gap-3">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup>
import Breadcrumbs from '@/Components/UI/Breadcrumbs.vue';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    breadcrumb: { type: Array, default: () => [] },
});
</script>
