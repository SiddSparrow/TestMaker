<!-- resources/js/Components/CopySuccessBanner.vue -->
<template>
    <div v-if="visible" 
         :class="[
             'copy-success-banner',
             type === 'success' ? 'type-success' :
             type === 'info' ? 'type-info' :
             type === 'warning' ? 'type-warning' : 'type-success'
         ]">
        <div class="banner-content">
            <div class="flex items-center">
                <!-- Ícone -->
                <div class="icon-container">
                    <slot name="icon">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </slot>
                </div>

                <!-- Conteúdo -->
                <div class="content-container">
                    <h3 class="title">
                        <slot name="title">{{ title }}</slot>
                    </h3>
                    
                    <div class="message">
                        <slot name="message">{{ message }}</slot>
                    </div>

                    <!-- Link para o original (se fornecido) -->
                    <div v-if="originalId || $slots.originalLink" class="original-link">
                        <slot name="originalLink">
                            <span class="original-label">{{ originalLabel }}:</span>
                            <a v-if="originalRoute && originalId"
                               :href="route(originalRoute, originalId)"
                               class="link">
                                #{{ originalId }}
                            </a>
                            <span v-else-if="originalId" class="original-id">
                                #{{ originalId }}
                            </span>
                        </slot>
                    </div>

                    <!-- Ações adicionais (slot) -->
                    <div v-if="$slots.actions" class="actions">
                        <slot name="actions"></slot>
                    </div>
                </div>

                <!-- Botão de fechar -->
                <button v-if="dismissible"
                        @click="dismiss"
                        class="close-button"
                        :title="closeTitle">
                    <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
    // Visibilidade
    show: {
        type: Boolean,
        default: false
    },
    
    // Conteúdo
    title: {
        type: String,
        default: 'Cópia Criada com Sucesso!'
    },
    message: {
        type: String,
        default: 'Esta é uma cópia do item original. Você pode editá-la livremente.'
    },
    
    // Tipo (estilo)
    type: {
        type: String,
        default: 'success',
        validator: (value) => ['success', 'info', 'warning'].includes(value)
    },
    
    // Informações do original
    originalId: [Number, String],
    originalLabel: {
        type: String,
        default: 'Original'
    },
    originalRoute: String, // Nome da rota para o original
    
    // Comportamento
    dismissible: {
        type: Boolean,
        default: true
    },
    autoDismiss: {
        type: Boolean,
        default: false
    },
    dismissAfter: {
        type: Number,
        default: 5000 // 5 segundos
    },
    closeTitle: {
        type: String,
        default: 'Fechar mensagem'
    },
    
    // Estado persistente
    storageKey: String // Chave para localStorage
})

const emit = defineEmits(['dismiss', 'show', 'hide'])

// Estado reativo
const visible = ref(props.show)
const hasBeenDismissed = ref(false)

// Verifica se já foi fechado anteriormente (persistência)
onMounted(() => {
    if (props.storageKey) {
        const dismissed = localStorage.getItem(props.storageKey)
        hasBeenDismissed.value = dismissed === 'true'
    }
    
    // Auto-dismiss
    if (props.autoDismiss && visible.value) {
        setTimeout(dismiss, props.dismissAfter)
    }
})

// Watch para prop show
watch(() => props.show, (newVal) => {
    visible.value = newVal && !hasBeenDismissed.value
    
    if (newVal && props.autoDismiss) {
        setTimeout(dismiss, props.dismissAfter)
    }
})

// Métodos
const dismiss = () => {
    visible.value = false
    hasBeenDismissed.value = true
    
    // Persiste no localStorage se houver chave
    if (props.storageKey) {
        localStorage.setItem(props.storageKey, 'true')
    }
    
    emit('dismiss')
    emit('hide')
}

const show = () => {
    visible.value = true
    hasBeenDismissed.value = false
    
    if (props.storageKey) {
        localStorage.removeItem(props.storageKey)
    }
    
    emit('show')
}
</script>

<style scoped>
.copy-success-banner {
    margin-bottom: 1rem;
    border-radius: 0.5rem;
    padding: 1rem;
    border-width: 1px;
    animation: fadeIn 0.5s ease-out;
}

.type-success {
    background: linear-gradient(to right, #f0fdf4, #ecfdf5);
    border-color: #bbf7d0;
}

.type-info {
    background: linear-gradient(to right, #eff6ff, #ecfeff);
    border-color: #bfdbfe;
}

.type-warning {
    background: linear-gradient(to right, #fefce8, #fffbeb);
    border-color: #fde68a;
}

.banner-content {
    width: 100%;
}

.icon-container {
    flex-shrink: 0;
}

.type-success .icon-container {
    height: 2.5rem;
    width: 2.5rem;
    border-radius: 9999px;
    background-color: #dcfce7;
    display: flex;
    align-items: center;
    justify-content: center;
}

.type-info .icon-container {
    height: 2.5rem;
    width: 2.5rem;
    border-radius: 9999px;
    background-color: #dbeafe;
    display: flex;
    align-items: center;
    justify-content: center;
}

.type-warning .icon-container {
    height: 2.5rem;
    width: 2.5rem;
    border-radius: 9999px;
    background-color: #fef3c7;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon {
    height: 1.5rem;
    width: 1.5rem;
}

.type-success .icon {
    color: #16a34a;
}

.type-info .icon {
    color: #2563eb;
}

.type-warning .icon {
    color: #d97706;
}

.content-container {
    margin-left: 1rem;
    flex: 1 1 0%;
}

.title {
    font-size: 1.125rem;
    line-height: 1.75rem;
    font-weight: 600;
}

.type-success .title {
    color: #166534;
}

.type-info .title {
    color: #1e40af;
}

.type-warning .title {
    color: #92400e;
}

.message {
    margin-top: 0.25rem;
}

.type-success .message {
    color: #15803d;
}

.type-info .message {
    color: #1d4ed8;
}

.type-warning .message {
    color: #b45309;
}

.original-link {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.type-success .original-link {
    color: #16a34a;
}

.type-info .original-link {
    color: #2563eb;
}

.type-warning .original-link {
    color: #d97706;
}

.original-label {
    font-weight: 500;
}

.link {
    margin-left: 0.25rem;
    text-decoration: underline;
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.link:hover {
    text-decoration: none;
}

.type-success .link {
    color: #166534;
}

.type-success .link:hover {
    color: #14532d;
}

.type-info .link {
    color: #1e40af;
}

.type-info .link:hover {
    color: #1e3a8a;
}

.type-warning .link {
    color: #92400e;
}

.type-warning .link:hover {
    color: #78350f;
}

.original-id {
    margin-left: 0.25rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 600;
}

.actions {
    margin-top: 0.75rem;
    display: flex;
    gap: 0.5rem;
}

.close-button {
    margin-left: auto;
    flex-shrink: 0;
    align-self: flex-start;
    transition-property: color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.type-success .close-button {
    color: #4ade80;
}

.type-success .close-button:hover {
    color: #16a34a;
}

.type-info .close-button {
    color: #60a5fa;
}

.type-info .close-button:hover {
    color: #2563eb;
}

.type-warning .close-button {
    color: #fbbf24;
}

.type-warning .close-button:hover {
    color: #d97706;
}

.close-icon {
    height: 1.25rem;
    width: 1.25rem;
}

/* Animações */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>