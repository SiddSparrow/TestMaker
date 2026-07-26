<template>
    <Link :href="href"
          :aria-current="active ? 'page' : undefined"
          :class="[
              'group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors',
              active
                ? 'bg-primary-50 text-primary-700 border-l-4 border-primary-600 pl-2.5'
                : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
          ]">
        <slot name="icon" v-if="$slots.icon">
            <div class="mr-3 flex-shrink-0">
                <slot name="icon" />
            </div>
        </slot>
        <span class="truncate"><slot /></span>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true
    },
    active: {
        type: Boolean,
        default: false
    }
});
</script>