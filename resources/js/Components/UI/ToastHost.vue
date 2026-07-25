<!--
    Host global de notificações. Fica montado no AppLayout e resolve o
    problema #2 da auditoria: o backend já envia `flash.success/error/...`
    em ~20 controllers, mas nada na UI lia isso. Aqui a gente observa o
    flash da página e também aceita toasts disparados no cliente via
    `useToast()` (ex.: falha de download que não passa pelo Inertia).
-->
<template>
    <div aria-live="polite" aria-atomic="true" class="fixed top-4 right-4 z-[9999] space-y-3 w-full max-w-sm">
        <ToastNotification
            v-for="toast in toasts"
            :key="toast.id"
            :type="toast.type"
            :title="toast.title"
            :message="toast.message"
            :duration="toast.duration"
            :action-text="toast.actionText"
            :on-action="toast.onAction"
            @close="dismiss(toast.id)"
        />
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import { useToast } from '@/composables/useToast';

const { toasts, dismiss, success, error, warning, info } = useToast();
const page = usePage();

const dispatchers = { success, error, warning, info };

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        for (const type of ['success', 'error', 'warning', 'info']) {
            if (flash[type]) {
                dispatchers[type](flash[type]);
            }
        }
    },
    { immediate: true, deep: true },
);
</script>
