<!--
    Botão base em Tailwind puro. Hoje o mesmo botão primário aparece em pelo
    menos 5 variações pelo app (azul arredondado maiúsculo, verde, gradiente,
    `.btn-elegant-primary`, `px-6 py-2.5` ad-hoc) — este componente concentra
    variantes/tamanhos num único lugar para as telas migrarem aos poucos.

    Uso:
      <BaseButton variant="primary">Salvar</BaseButton>
      <BaseButton variant="danger" :loading="form.processing">Excluir</BaseButton>
      <BaseButton href="/questions/1" variant="outline">Ver</BaseButton>
      <BaseButton icon-only aria-label="Editar questão" variant="ghost"><PencilIcon class="w-4 h-4" /></BaseButton>
-->
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'danger', 'outline', 'ghost'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    iconOnly: { type: Boolean, default: false },
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    ariaLabel: { type: String, default: null },
});

if (import.meta.env.DEV && props.iconOnly && !props.ariaLabel) {
    // eslint-disable-next-line no-console
    console.warn('[BaseButton] icon-only precisa da prop aria-label para ser utilizável por leitor de tela.');
}

const isLink = computed(() => !!props.href);
const isDisabled = computed(() => props.disabled || props.loading);

const sizeClasses = computed(() => {
    if (props.iconOnly) {
        return { sm: 'p-1.5', md: 'p-2', lg: 'p-2.5' }[props.size];
    }
    return { sm: 'px-3 py-1.5 text-sm', md: 'px-4 py-2 text-sm', lg: 'px-6 py-2.5 text-base' }[props.size];
});

const variantClasses = computed(() => ({
    primary: 'bg-blue-600 text-white border border-transparent hover:bg-blue-700 focus-visible:ring-blue-500 disabled:bg-blue-300',
    secondary: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus-visible:ring-blue-500 disabled:opacity-50',
    danger: 'bg-red-600 text-white border border-transparent hover:bg-red-700 focus-visible:ring-red-500 disabled:bg-red-300',
    outline: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus-visible:ring-blue-500 disabled:opacity-50',
    ghost: 'bg-transparent text-gray-600 border border-transparent hover:bg-gray-100 focus-visible:ring-blue-500 disabled:opacity-50',
}[props.variant]));
</script>

<template>
    <component
        :is="isLink ? Link : 'button'"
        :href="isLink ? href : undefined"
        :type="!isLink ? type : undefined"
        :disabled="!isLink ? isDisabled : undefined"
        :aria-disabled="isLink && isDisabled ? 'true' : undefined"
        :aria-label="ariaLabel"
        :aria-busy="loading ? 'true' : undefined"
        :class="[
            'inline-flex items-center justify-center gap-2 rounded-md font-medium transition-colors',
            'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
            'disabled:cursor-not-allowed',
            sizeClasses,
            variantClasses,
        ]"
    >
        <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <slot />
    </component>
</template>
