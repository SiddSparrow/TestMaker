<!-- resources/js/Pages/Dashboard.vue -->
<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="space-y-6">
            <!-- Cards de estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 animate-slide-up delay-150">
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
                    color="orange"
                    :link="route('topics.index')"
                />

                <StatCard
                    title="Etiquetas"
                    :value="stats.total_tags"
                    icon="TagIcon"
                    color="orange"
                    :link="route('tags.index')"
                />
            </div>
            <!-- Ações rápidas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 animate-slide-up delay-300">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Ações Rápidas</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Link :href="route('questions.create')"
                          class="inline-flex items-center justify-center px-4 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nova Questão
                    </Link>
                    
                    <Link :href="route('exams.create')"
                          class="inline-flex items-center justify-center px-4 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Criar Prova
                    </Link>
                    
                    <Link :href="route('documents.create')"
                          class="inline-flex items-center justify-center px-4 py-3 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        Importar PDF
                    </Link>
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

        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StatCard from '@/Components/StatCard.vue';
import QuestionList from '@/Components/QuestionList.vue';
import ExamList from '@/Components/ExamList.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    stats: Object,
    recent_questions: Array,
    recent_exams: Array,
});
</script>