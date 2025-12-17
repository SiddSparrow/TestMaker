<template>
    <AppLayout>
        <div class="container mx-auto p-4">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-4">Exame: {{ exam.title }}</h1>
                <p class="mb-6">{{ exam.description }}</p>

                <div v-if="exam.questions.length === 0" class="text-center text-gray-500">
                    Nenhuma questão adicionada a este exame.
                </div>

                <div v-else>
                    <table class="w-full table-auto border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border px-4 py-2 text-left">Ordem</th>
                                <th class="border px-4 py-2 text-left">Questão</th>
                                <th class="border px-4 py-2 text-left">Matéria</th>
                                <th class="border px-4 py-2 text-left">Tipo</th>
                                <th class="border px-4 py-2 text-left">Pontos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(q, index) in exam.questions" :key="q.id" class="hover:bg-gray-50">
                                <td class="border px-4 py-2 align-top">{{ index + 1 }}</td>
                                <td class="border px-4 py-2 align-top" v-html="q.content"></td>
                                <td class="border px-4 py-2 align-top">{{ q.subject.name }}</td>
                                <td class="border px-4 py-2 align-top">{{ q.questionType.name }}</td>
                                <td class="border px-4 py-2 align-top">{{ q.pivot.points_override ?? q.default_points }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
const props = defineProps({
    exam: Object,
});

</script>