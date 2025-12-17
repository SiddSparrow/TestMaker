<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Alternativas</h2>
                <p v-if="isVerdadeiroFalso" class="text-sm text-gray-600 mt-1">
                    Para V/F: marque cada afirmação como Verdadeira (✓) ou Falsa (✗)
                </p>
            </div>
            <button v-if="canAddAlternative"
                    type="button"
                    @click="addAlternative"
                    class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                + Adicionar
            </button>
        </div>

        <div class="space-y-3">
            <div v-for="(alternative, index) in localAlternatives" 
                 :key="alternative.id || index"
                 class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors">
                
                <!-- Checkbox/Radio para resposta correta -->
                <div class="flex items-center pt-2">
                    <input v-if="allowMultipleCorrect"
                           type="checkbox"
                           v-model="alternative.is_correct"
                           @change="emitUpdate"
                           class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                    <input v-else
                           type="radio"
                           :name="`correct-answer-${componentId}`"
                           :checked="alternative.is_correct"
                           @change="setCorrectAnswer(index)"
                           class="w-5 h-5 text-green-600 border-gray-300 focus:ring-green-500">
                </div>

                <!-- Letra/Número da alternativa -->
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-semibold flex-shrink-0 mt-1">
                    {{ getAlternativeLabel(index) }}
                </div>

                <!-- Conteúdo -->
                <div class="flex-1">
                    <textarea v-model="alternative.content"
                              @input="emitUpdate"
                              rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 resize-none"
                              :class="{ 'border-red-500': getError(index) }"
                              :placeholder="getPlaceholder(index)"></textarea>
                    <p v-if="getError(index)" 
                       class="mt-1 text-sm text-red-600">
                        {{ getError(index) }}
                    </p>
                    <p v-if="isVerdadeiroFalso" class="mt-1 text-xs font-medium"
                       :class="alternative.is_correct ? 'text-green-600' : 'text-red-600'">
                        {{ alternative.is_correct ? '✓ Verdadeiro' : '✗ Falso' }}
                    </p>
                </div>

                <!-- Botão remover -->
                <button v-if="canRemoveAlternative"
                        type="button"
                        @click="removeAlternative(index)"
                        class="p-2 text-red-600 hover:bg-red-50 rounded-md transition-colors mt-1"
                        :title="`Remover alternativa ${getAlternativeLabel(index)}`">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Avisos -->
        <div v-if="showWarnings" class="mt-4 space-y-2">
            <p v-if="!hasCorrectAnswer && !isVerdadeiroFalso" 
               class="text-sm text-amber-600 flex items-center">
                <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ allowMultipleCorrect ? 'Selecione pelo menos uma resposta correta' : 'Marque a resposta correta' }}
            </p>
            
            <p v-if="hasEmptyContent" 
               class="text-sm text-amber-600 flex items-center">
                <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Preencha todas as alternativas antes de salvar
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    questionType: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    },
    minAlternatives: {
        type: Number,
        default: 2
    },
    maxAlternatives: {
        type: Number,
        default: 10
    },
    showWarnings: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue']);

// ID único para o componente (para evitar conflito de radio buttons)
const componentId = ref(Math.random().toString(36).substring(7));

// Cópia local das alternativas
const localAlternatives = ref([]);

// Inicializa com os valores do modelValue
onMounted(() => {
    if (props.modelValue.length > 0) {
        localAlternatives.value = JSON.parse(JSON.stringify(props.modelValue));
    } else {
        // Inicializa com alternativas padrão se vazio
        initializeDefaultAlternatives();
    }
});

// Watch para sincronizar mudanças externas
watch(() => props.modelValue, (newVal) => {
    if (JSON.stringify(newVal) !== JSON.stringify(localAlternatives.value)) {
        localAlternatives.value = JSON.parse(JSON.stringify(newVal));
    }
}, { deep: true });

// Computed: tipo de questão
const isVerdadeiroFalso = computed(() => {
    return props.questionType?.slug === 'verdadeiro-falso';
});

const isMultiplaEscolha = computed(() => {
    return props.questionType?.slug === 'multipla-escolha';
});

const isMultiplaResposta = computed(() => {
    return props.questionType?.slug === 'multipla-resposta';
});

// Computed: permite múltiplas respostas corretas
const allowMultipleCorrect = computed(() => {
    return isMultiplaResposta.value || isVerdadeiroFalso.value;
});

// Computed: pode adicionar alternativa
const canAddAlternative = computed(() => {
    if (isVerdadeiroFalso.value) return false; // V/F tem número fixo
    return localAlternatives.value.length < props.maxAlternatives;
});

// Computed: pode remover alternativa
const canRemoveAlternative = computed(() => {
    return localAlternatives.value.length > props.minAlternatives;
});

// Computed: tem pelo menos uma resposta correta
const hasCorrectAnswer = computed(() => {
    return localAlternatives.value.some(alt => alt.is_correct);
});

// Computed: tem conteúdo vazio
const hasEmptyContent = computed(() => {
    return localAlternatives.value.some(alt => !alt.content || alt.content.trim() === '');
});

// Inicializa alternativas padrão baseado no tipo
const initializeDefaultAlternatives = () => {
    if (isVerdadeiroFalso.value) {
        localAlternatives.value = [
            { content: 'Verdadeiro', is_correct: false, order: 1 },
            { content: 'Falso', is_correct: false, order: 2 }
        ];
    } else {
        localAlternatives.value = [
            { content: '', is_correct: false, order: 1 },
            { content: '', is_correct: false, order: 2 }
        ];
    }
    emitUpdate();
};

// Adiciona alternativa
const addAlternative = () => {
    if (!canAddAlternative.value) return;
    
    localAlternatives.value.push({
        content: '',
        is_correct: false,
        order: localAlternatives.value.length + 1
    });
    emitUpdate();
};

// Remove alternativa
const removeAlternative = (index) => {
    if (!canRemoveAlternative.value) return;
    
    localAlternatives.value.splice(index, 1);
    
    // Reordena
    localAlternatives.value.forEach((alt, i) => {
        alt.order = i + 1;
    });
    
    emitUpdate();
};

// Define resposta correta (para radio button - apenas uma correta)
const setCorrectAnswer = (index) => {
    localAlternatives.value.forEach((alt, i) => {
        alt.is_correct = i === index;
    });
    emitUpdate();
};

// Emite atualização para o pai
const emitUpdate = () => {
    emit('update:modelValue', localAlternatives.value);
};

// Pega o label da alternativa (A, B, C ou 1, 2, 3)
const getAlternativeLabel = (index) => {
    return isVerdadeiroFalso.value 
        ? (index + 1).toString() 
        : String.fromCharCode(65 + index);
};

// Pega o placeholder da alternativa
const getPlaceholder = (index) => {
    if (isVerdadeiroFalso.value) {
        return `Afirmação ${index + 1}`;
    }
    return `Alternativa ${String.fromCharCode(65 + index)}`;
};

// Pega erro específico da alternativa
const getError = (index) => {
    return props.errors[`alternatives.${index}.content`];
};

// Expõe método de validação para o componente pai
defineExpose({
    validate: () => {
        return {
            isValid: hasCorrectAnswer.value && !hasEmptyContent.value,
            hasCorrectAnswer: hasCorrectAnswer.value,
            hasEmptyContent: hasEmptyContent.value
        };
    }
});
</script>

<style scoped>
/* Animação suave para adicionar/remover */
.space-y-3 > * {
    transition: all 0.2s ease;
}
</style>