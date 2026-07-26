<!-- resources/js/Components/ToastNotification.vue -->
<template>
    <transition name="toast">
        <div v-if="show"
             :class="[
                 'p-4 rounded-lg shadow-lg max-w-sm',
                 type === 'success' ? 'bg-green-50 border-l-4 border-green-500' :
                 type === 'error' ? 'bg-red-50 border-l-4 border-red-500' :
                 type === 'warning' ? 'bg-yellow-50 border-l-4 border-yellow-500' :
                 'bg-blue-50 border-l-4 border-blue-500'
             ]">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg v-if="type === 'success'" class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg v-else-if="type === 'error'" class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L3.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <svg v-else class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-gray-900">
                        {{ title }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ message }}
                    </p>
                    <div class="mt-3 flex space-x-3">
                        <button v-if="actionText"
                                @click="onAction"
                                :class="[
                                    'text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2',
                                    type === 'success' ? 'text-green-600 hover:text-green-500 focus:ring-green-500' :
                                    type === 'error' ? 'text-red-600 hover:text-red-500 focus:ring-red-500' :
                                    'text-blue-600 hover:text-blue-500 focus:ring-blue-500'
                                ]">
                            {{ actionText }}
                        </button>
                        <button @click="close"
                                class="text-sm font-medium text-gray-700 hover:text-gray-900">
                            Fechar
                        </button>
                    </div>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button @click="close"
                            class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Fechar</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
    title: String,
    message: String,
    type: {
        type: String,
        default: 'success',
        validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
    },
    duration: {
        type: Number,
        default: 5000
    },
    actionText: String,
    onAction: Function
})

const emit = defineEmits(['close'])
const show = ref(true)
let timeoutId = null

const close = () => {
    show.value = false
    setTimeout(() => {
        emit('close')
    }, 300)
}

const onAction = () => {
    if (props.onAction) {
        props.onAction()
    }
    close()
}

onMounted(() => {
    if (props.duration > 0) {
        timeoutId = setTimeout(close, props.duration)
    }
})
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.toast-enter-to,
.toast-leave-from {
    opacity: 1;
    transform: translateX(0);
}
</style>