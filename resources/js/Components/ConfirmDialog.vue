<template>
    <Modal :show="show" max-width="md" @close="cancel">
        <!-- Icon -->
        <div class="flex items-center justify-center pt-8 pb-4">
            <div class="flex items-center justify-center w-16 h-16 rounded-full"
                 :class="iconBackgroundClass">
                <svg class="w-8 h-8" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="type === 'danger'"
                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    <path v-else-if="type === 'warning'"
                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    <path v-else-if="type === 'info'"
                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    <path v-else
                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <!-- Content -->
        <div class="px-6 pb-6 text-center">
            <h3 class="text-xl font-semibold text-gray-900 mb-3">
                {{ title }}
            </h3>
            <div class="text-sm text-gray-600 leading-relaxed" v-html="message"></div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 px-6 pb-6">
            <button @click="cancel"
                    class="flex-1 px-4 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                {{ cancelText }}
            </button>
            <button @click="confirm"
                    class="flex-1 px-4 py-2.5 text-white rounded-lg transition-colors font-medium"
                    :class="confirmButtonClass">
                {{ confirmText }}
            </button>
        </div>
    </Modal>
</template>

<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        required: true
    },
    message: {
        type: String,
        required: true
    },
    confirmText: {
        type: String,
        default: 'Confirmar'
    },
    cancelText: {
        type: String,
        default: 'Cancelar'
    },
    type: {
        type: String,
        default: 'warning',
        validator: (value) => ['danger', 'warning', 'info', 'success'].includes(value)
    }
});

const emit = defineEmits(['update:show', 'confirm', 'cancel']);

const iconBackgroundClass = computed(() => {
    const classes = {
        danger: 'bg-red-100',
        warning: 'bg-amber-100',
        info: 'bg-blue-100',
        success: 'bg-green-100'
    };
    return classes[props.type] || classes.warning;
});

const iconColorClass = computed(() => {
    const classes = {
        danger: 'text-red-600',
        warning: 'text-amber-600',
        info: 'text-blue-600',
        success: 'text-green-600'
    };
    return classes[props.type] || classes.warning;
});

const confirmButtonClass = computed(() => {
    const classes = {
        danger: 'bg-red-600 hover:bg-red-700',
        warning: 'bg-amber-600 hover:bg-amber-700',
        info: 'bg-blue-600 hover:bg-blue-700',
        success: 'bg-green-600 hover:bg-green-700'
    };
    return classes[props.type] || classes.warning;
});

const cancel = () => {
    emit('update:show', false);
    emit('cancel');
};

const confirm = () => {
    emit('update:show', false);
    emit('confirm');
};
</script>