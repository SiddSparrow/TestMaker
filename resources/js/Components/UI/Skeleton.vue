<!--
    Placeholder de carregamento. A classe `.skeleton` já existia em
    components.css com 0 usos — nenhuma tabela mostrava progresso ao filtrar
    ou paginar, a tela simplesmente congelava. Este componente substitui
    aquela classe morta por algo de fato usado pelas listagens.
-->
<template>
    <div class="animate-pulse space-y-2" role="status" aria-label="Carregando">
        <div
            v-for="row in rows"
            :key="row"
            class="rounded bg-gray-200"
            :class="rowClass"
        ></div>
    </div>
</template>

<script setup>
defineProps({
    rows: { type: Number, default: 1 },
    rowClass: { type: String, default: 'h-4 w-full' },
});
</script>
