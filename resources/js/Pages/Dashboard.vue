<!-- resources/js/Pages/Dashboard.vue -->
<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="space-y-6">
            <!-- Cards de estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 animate-slide-up delay-150">
                <StatCard
                    title="Questões Ativas"
                    :value="stats.total_questions"
                    icon="QuestionMarkCircleIcon"
                    color="blue"
                    :link="route('questions.index')"
                />
                <StatCard
                    title="Provas Criadas"
                    :value="stats.total_exams"
                    icon="DocumentTextIcon"
                    color="green"
                    :link="route('exams.index')"
                />
                <StatCard
                    title="Matérias"
                    :value="stats.total_subjects"
                    icon="BookOpenIcon"
                    color="purple"
                    :link="route('subjects.index')"
                />
                <StatCard
                    title="Tópicos"
                    :value="stats.total_topics"
                    icon="FolderIcon"
                    color="indigo"
                    :link="route('topics.index')"
                />
                <StatCard
                    title="Etiquetas"
                    :value="stats.total_tags"
                    icon="TagIcon"
                    color="pink"
                    :link="route('tags.index')"
                />
                <StatCard
                    title="Documentos Processados"
                    :value="stats.total_documents"
                    icon="ArrowUpTrayIcon"
                    color="orange"
                    :link="route('documents.index')"
                />
            </div>
            <!-- Ações rápidas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 animate-slide-up delay-300">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Ações Rápidas</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <BaseButton :href="route('questions.create')" size="lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nova Questão
                    </BaseButton>

                    <BaseButton :href="route('exams.create')" size="lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Criar Prova
                    </BaseButton>

                    <BaseButton :href="route('documents.create')" size="lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        Importar PDF
                    </BaseButton>
                </div>
            </div>


            <!-- Grid de duas colunas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-slide-up delay-250">
                <!-- Últimas questões -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">Últimas Questões</h3>
                        <Link :href="route('questions.index')" class="text-sm text-blue-600 hover:text-blue-800">
                            Ver todas →
                        </Link>
                    </div>
                    <div class="p-4">
                        <QuestionList :questions="recent_questions" />
                    </div>
                </div>

                <!-- Provas recentes -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">Provas Recentes</h3>
                        <Link :href="route('exams.index')" class="text-sm text-blue-600 hover:text-blue-800">
                            Ver todas →
                        </Link>
                    </div>
                    <div class="p-4">
                        <ExamList :exams="recent_exams" />
                    </div>
                </div>
            </div>

            <!-- Matérias mais usadas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 animate-slide-up delay-300">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Matérias Mais Usadas</h3>
                    <Link :href="route('subjects.index')" class="text-sm text-blue-600 hover:text-blue-800">
                        Ver todas →
                    </Link>
                </div>
                <div class="p-4">
                    <ul v-if="most_used_subjects.length" class="divide-y divide-gray-100">
                        <li v-for="subject in most_used_subjects" :key="subject.id"
                            class="py-3 flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: subject.color }" />
                                <span class="text-sm font-medium text-gray-800 truncate">{{ subject.name }}</span>
                            </div>
                            <span class="text-sm text-gray-500 shrink-0">
                                {{ subject.question_count }} {{ Number(subject.question_count) === 1 ? 'questão' : 'questões' }}
                            </span>
                        </li>
                    </ul>
                    <div v-else class="text-center py-8 text-gray-500">
                        Nenhuma matéria com questões ainda
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StatCard from '@/Components/StatCard.vue';
import QuestionList from '@/Components/QuestionList.vue';
import ExamList from '@/Components/ExamList.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';

defineProps({
    stats: Object,
    recent_questions: Array,
    recent_exams: Array,
    most_used_subjects: {
        type: Array,
        default: () => []
    },
});
</script>