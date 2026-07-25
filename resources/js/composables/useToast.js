import { reactive } from 'vue';

// Store simples e compartilhado entre todos os componentes que chamarem
// useToast() — não precisa de Pinia/Vuex para um caso de uso tão pequeno.
const toasts = reactive([]);

let nextId = 1;

function push(type, message, options = {}) {
    const id = nextId++;

    toasts.push({
        id,
        type,
        title: options.title ?? defaultTitle(type),
        message,
        duration: options.duration ?? 5000,
        actionText: options.actionText,
        onAction: options.onAction,
    });

    return id;
}

function dismiss(id) {
    const index = toasts.findIndex((toast) => toast.id === id);
    if (index !== -1) {
        toasts.splice(index, 1);
    }
}

function defaultTitle(type) {
    const titles = {
        success: 'Sucesso',
        error: 'Erro',
        warning: 'Atenção',
        info: 'Informação',
    };
    return titles[type] || titles.info;
}

export function useToast() {
    return {
        toasts,
        dismiss,
        success: (message, options) => push('success', message, options),
        error: (message, options) => push('error', message, options),
        warning: (message, options) => push('warning', message, options),
        info: (message, options) => push('info', message, options),
    };
}
