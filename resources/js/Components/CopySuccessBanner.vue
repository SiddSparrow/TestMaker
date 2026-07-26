<template>
    <div v-if="visible" class="mb-4 rounded-lg border p-4 animate-fade-in" :class="tone.container">
        <div class="flex items-start">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full" :class="tone.iconBg">
                <slot name="icon">
                    <svg class="h-6 w-6" :class="tone.icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </slot>
            </div>

            <div class="ml-4 flex-1">
                <h3 class="text-lg font-semibold" :class="tone.title">
                    <slot name="title">{{ title }}</slot>
                </h3>

                <div class="mt-1" :class="tone.message">
                    <slot name="message">{{ message }}</slot>
                </div>

                <div v-if="originalId || $slots.originalLink" class="mt-2 text-sm" :class="tone.message">
                    <slot name="originalLink">
                        <span class="font-medium">{{ originalLabel }}:</span>
                        <a v-if="originalRoute && originalId"
                           :href="route(originalRoute, originalId)"
                           class="ml-1 underline transition-colors hover:no-underline"
                           :class="tone.link">
                            #{{ originalId }}
                        </a>
                        <span v-else-if="originalId" class="ml-1 font-mono font-semibold">
                            #{{ originalId }}
                        </span>
                    </slot>
                </div>

                <div v-if="$slots.actions" class="mt-3 flex gap-2">
                    <slot name="actions"></slot>
                </div>
            </div>

            <button v-if="dismissible"
                    type="button"
                    @click="dismiss"
                    :title="closeTitle"
                    :aria-label="closeTitle"
                    class="ml-auto flex-shrink-0 self-start transition-colors"
                    :class="tone.close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Cópia Criada com Sucesso!',
    },
    message: {
        type: String,
        default: 'Esta é uma cópia do item original. Você pode editá-la livremente.',
    },
    type: {
        type: String,
        default: 'success',
        validator: (value) => ['success', 'info', 'warning'].includes(value),
    },
    originalId: [Number, String],
    originalLabel: {
        type: String,
        default: 'Original',
    },
    originalRoute: String,
    dismissible: {
        type: Boolean,
        default: true,
    },
    autoDismiss: {
        type: Boolean,
        default: false,
    },
    dismissAfter: {
        type: Number,
        default: 5000,
    },
    closeTitle: {
        type: String,
        default: 'Fechar mensagem',
    },
    storageKey: String,
});

const emit = defineEmits(['dismiss', 'show', 'hide']);

const visible = ref(props.show);
const hasBeenDismissed = ref(false);

const tones = {
    success: {
        container: 'bg-gradient-to-r from-green-50 to-emerald-50 border-green-200',
        iconBg: 'bg-green-100',
        icon: 'text-green-600',
        title: 'text-green-800',
        message: 'text-green-700',
        link: 'text-green-800 hover:text-green-900',
        close: 'text-green-400 hover:text-green-600',
    },
    info: {
        container: 'bg-gradient-to-r from-blue-50 to-cyan-50 border-blue-200',
        iconBg: 'bg-blue-100',
        icon: 'text-blue-600',
        title: 'text-blue-800',
        message: 'text-blue-700',
        link: 'text-blue-800 hover:text-blue-900',
        close: 'text-blue-400 hover:text-blue-600',
    },
    warning: {
        container: 'bg-gradient-to-r from-yellow-50 to-amber-50 border-yellow-200',
        iconBg: 'bg-yellow-100',
        icon: 'text-yellow-600',
        title: 'text-yellow-800',
        message: 'text-yellow-700',
        link: 'text-yellow-800 hover:text-yellow-900',
        close: 'text-yellow-400 hover:text-yellow-600',
    },
};

const tone = computed(() => tones[props.type] || tones.success);

onMounted(() => {
    if (props.storageKey) {
        const dismissed = localStorage.getItem(props.storageKey);
        hasBeenDismissed.value = dismissed === 'true';
    }

    if (props.autoDismiss && visible.value) {
        setTimeout(dismiss, props.dismissAfter);
    }
});

watch(() => props.show, (newVal) => {
    visible.value = newVal && !hasBeenDismissed.value;

    if (newVal && props.autoDismiss) {
        setTimeout(dismiss, props.dismissAfter);
    }
});

const dismiss = () => {
    visible.value = false;
    hasBeenDismissed.value = true;

    if (props.storageKey) {
        localStorage.setItem(props.storageKey, 'true');
    }

    emit('dismiss');
    emit('hide');
};
</script>
