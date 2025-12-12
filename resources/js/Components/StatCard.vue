<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">{{ title }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ value }}</p>
            </div>
            <div :class="[
                'rounded-lg p-3',
                color === 'blue' ? 'bg-blue-50 text-blue-600' :
                color === 'green' ? 'bg-green-50 text-green-600' :
                color === 'purple' ? 'bg-purple-50 text-purple-600' :
                'bg-orange-50 text-orange-600'
            ]">
                <component :is="iconComponent" class="w-6 h-6" />
            </div>
        </div>
        <!-- <div v-if="change" class="mt-4 flex items-center">
            <ArrowTrendingUpIcon v-if="change.includes('+')" class="w-4 h-4 text-green-500" />
            <ArrowTrendingDownIcon v-else class="w-4 h-4 text-red-500" />
            <span :class="[
                'ml-1 text-sm font-medium',
                change.includes('+') ? 'text-green-600' : 'text-red-600'
            ]">
                {{ change }}
            </span>
            <span class="ml-2 text-sm text-gray-500">desde o último mês</span>
        </div> -->
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    QuestionMarkCircleIcon,
    DocumentTextIcon,
    BookOpenIcon,
    ArrowUpTrayIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: String,
    color: {
        type: String,
        default: 'blue',
        validator: (value) => ['blue', 'green', 'purple', 'orange'].includes(value)
    },
    change: String
});

const iconComponent = computed(() => {
    const icons = {
        QuestionMarkCircleIcon,
        DocumentTextIcon,
        BookOpenIcon,
        ArrowUpTrayIcon
    };
    return icons[props.icon] || QuestionMarkCircleIcon;
});
</script>