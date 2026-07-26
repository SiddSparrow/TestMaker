<!--
    Badge de status genérico e "burro": cada tela decide o rótulo (a
    terminologia já varia legitimamente por domínio — questão "Ativa", prova
    "Agendada", documento "Processando" — e não é isso que a auditoria
    aponta como inconsistência) mas todas usam a mesma marcação e cores.
-->
<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="toneClasses[tone]"
    >
        <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="dotClasses[tone]"></span>
        {{ label }}
    </span>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    tone: {
        type: String,
        default: 'gray',
        validator: (v) => ['green', 'red', 'blue', 'yellow', 'gray'].includes(v),
    },
    dot: { type: Boolean, default: false },
});

const toneClasses = {
    green: 'bg-green-100 text-green-800',
    red: 'bg-red-100 text-red-800',
    blue: 'bg-blue-100 text-blue-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    gray: 'bg-gray-100 text-gray-800',
};

const dotClasses = {
    green: 'bg-green-500',
    red: 'bg-red-500',
    blue: 'bg-blue-500',
    yellow: 'bg-yellow-500',
    gray: 'bg-gray-400',
};
</script>
