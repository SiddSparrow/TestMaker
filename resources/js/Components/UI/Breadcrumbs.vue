<!--
    Trilha de navegação simples. O CSS para breadcrumbs já existia em
    components.css sem nenhum consumidor — este componente substitui aquelas
    classes mortas por algo usado de fato nas telas de 2º nível.

    Uso:
      <Breadcrumbs :items="[{ label: 'Questões', href: route('questions.index') }, { label: 'Editar' }]" />
-->
<template>
    <nav aria-label="Trilha de navegação">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
            <li>
                <Link :href="route('dashboard')" class="hover:text-gray-700">Início</Link>
            </li>
            <template v-for="(item, index) in items" :key="index">
                <li aria-hidden="true" class="text-gray-300">/</li>
                <li>
                    <Link v-if="item.href" :href="item.href" class="hover:text-gray-700">{{ item.label }}</Link>
                    <span v-else aria-current="page" class="text-gray-700">{{ item.label }}</span>
                </li>
            </template>
        </ol>
    </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: { type: Array, default: () => [] },
});
</script>
