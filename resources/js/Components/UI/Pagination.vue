<!--
    Paginação padrão sobre o formato de `links` do paginator do Laravel.
    Usa <Link preserve-scroll preserve-state> — a paginação de Questões e
    Documentos hoje usa <a href>, que recarrega a página inteira e perde o
    scroll; a de Provas nem existe. Este componente vira o único padrão.
-->
<template>
    <nav v-if="links.length > 3" aria-label="Paginação" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <p v-if="summary" class="text-sm text-gray-600">{{ summary }}</p>
        <ul class="flex flex-wrap items-center gap-1">
            <li v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    :aria-current="link.active ? 'page' : undefined"
                    :aria-label="ariaLabelFor(link)"
                    :class="[
                        'inline-flex min-w-[2.25rem] items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                        link.active
                            ? 'bg-blue-600 text-white'
                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
                    ]"
                    v-html="link.label"
                />
                <span
                    v-else
                    aria-disabled="true"
                    class="inline-flex min-w-[2.25rem] items-center justify-center rounded-md px-3 py-1.5 text-sm text-gray-400"
                    v-html="link.label"
                />
            </li>
        </ul>
    </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, required: true },
    summary: { type: String, default: null },
});

const ariaLabelFor = (link) => {
    const text = link.label.replace(/<[^>]*>/g, '').trim();
    if (text === '&laquo; Previous' || text === '«') return 'Página anterior';
    if (text === 'Next &raquo;' || text === '»') return 'Próxima página';
    return `Página ${text}`;
};
</script>
