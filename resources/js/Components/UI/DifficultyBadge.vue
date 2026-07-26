<!--
    A dificuldade de uma questão hoje tem 3 aparências diferentes pelo app
    (gradientes em components.css, bg-green-100/yellow-100/red-100 em
    Questions/Show.vue, outra variação no ExamBuilder) e o Dashboard chega a
    mostrar o valor cru em inglês ("easy"/"medium"/"hard"). Este componente
    é a única fonte da verdade para rótulo, cor e ícone.
-->
<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="tone.classes"
    >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tone.icon" />
        </svg>
        {{ tone.label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    level: {
        type: String,
        required: true,
        validator: (v) => ['easy', 'medium', 'hard'].includes(v),
    },
});

const map = {
    easy: {
        label: 'Fácil',
        classes: 'bg-green-100 text-green-800',
        icon: 'M5 13l4 4L19 7',
    },
    medium: {
        label: 'Média',
        classes: 'bg-yellow-100 text-yellow-800',
        icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    },
    hard: {
        label: 'Difícil',
        classes: 'bg-red-100 text-red-800',
        icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L3.346 16.5c-.77.833.192 2.5 1.732 2.5z',
    },
};

const tone = computed(() => map[props.level]);
</script>
