<!--
    Campo de formulário com `label for`/`id` associados corretamente — hoje
    praticamente nenhum formulário interno faz essa associação (só o Login
    faz certo), então clicar no rótulo não foca o campo e leitores de tela
    não conseguem ligar um ao outro.

    Uso:
      <FormField label="Matéria" required :error="form.errors.subject_id" v-slot="{ id }">
        <select :id="id" v-model="form.subject_id" class="...") />
      </FormField>
-->
<template>
    <div>
        <InputLabel v-if="label" :for="fieldId">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </InputLabel>
        <div :class="label ? 'mt-1' : ''">
            <slot :id="fieldId" :described-by="describedBy" />
        </div>
        <p v-if="hint && !error" :id="`${fieldId}-hint`" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
        <InputError v-if="error" :id="`${fieldId}-error`" :message="error" class="mt-1" />
    </div>
</template>

<script setup>
import { computed, useId } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    label: { type: String, default: null },
    required: { type: Boolean, default: false },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    id: { type: String, default: null },
});

const generatedId = useId();
const fieldId = computed(() => props.id || `field-${generatedId}`);
const describedBy = computed(() => {
    if (props.error) return `${fieldId.value}-error`;
    if (props.hint) return `${fieldId.value}-hint`;
    return undefined;
});
</script>
