<template>
    <AppLayout>
        <Head title="Questões" />

        <div class="space-y-6 fade-in">
            <!-- Cabeçalho Elegante -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 ml-mr-1">
                <div class="slide-up" style="animation-delay: 100ms">
                    <h1 class="page-title-elegant">Questões</h1>
                    <p class="page-subtitle-elegant">Gerencie seu banco de questões de forma inteligente</p>
                </div>
                <div class="flex items-center space-x-3 slide-up" style="animation-delay: 200ms">
                    <!-- Filtros rápidos -->
                    <div class="flex items-center space-x-2">
                        <button @click="toggleFilterPanel"
                                class="btn-elegant btn-elegant-outline flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            Filtros
                        </button>
                        <a :href="route('questions.create')"
                        class="btn-elegant btn-elegant-primary flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nova Questão
                        </a>
                    </div>
                </div>
            </div>

            <!-- Painel de filtros Elegante -->
            <div v-if="showFilters" class="card-elegant slide-up" style="animation-delay: 150ms">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 ml-mr-1">
                    <!-- Busca -->
                    <div class="form-group-elegant">
                        <label class="form-label-elegant">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Buscar
                        </label>
                        <input v-model="form.search"
                            type="text"
                            placeholder="Digite para buscar..."
                            class="form-control-elegant">
                    </div>

                    <!-- Matéria -->
                    <div class="form-group-elegant">
                        <label class="form-label-elegant">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            Matéria
                        </label>
                        <select v-model="form.subject_id"
                                class="form-control-elegant">
                            <option value="">Todas as matérias</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                {{ subject.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Dificuldade -->
                    <div class="form-group-elegant">
                        <label class="form-label-elegant">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                            Dificuldade
                        </label>
                        <select v-model="form.difficulty_level"
                                class="form-control-elegant">
                            <option value="">Todas as dificuldades</option>
                            <option v-for="level in difficulty_levels" :key="level.value" :value="level.value">
                                {{ level.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="form-group-elegant">
                        <label class="form-label-elegant">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Status
                        </label>
                        <select v-model="form.is_active"
                                class="form-control-elegant">
                            <option value="">Todos os status</option>
                            <option value="1">Ativas</option>
                            <option value="0">Inativas</option>
                        </select>
                    </div>

                    <!-- Botões -->
                    <div class="flex items-end space-x-2">
                        <button @click="applyFilters"
                                class="btn-elegant btn-elegant-primary flex-1"
                                style="margin-top: 2rem;margin-bottom: auto;">
                            Aplicar Filtros
                        </button>
                        <button @click="resetFilters"
                                class="btn-elegant btn-elegant-secondary"
                                style="margin-top: 2.3rem;margin-bottom: auto;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estatísticas Elegantes -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 ml-mr-1">
                <div class="stats-card-elegant slide-up" style="animation-delay: 200ms">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Total</div>
                    <div class="stats-value-elegant">{{ stats.total }}</div>
                    <div class="text-xs text-gray-400">questões no banco</div>
                </div>
                <div class="stats-card-elegant slide-up" style="animation-delay: 250ms">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Ativas</div>
                    <div class="stats-value-elegant text-green-600">{{ stats.active }}</div>
                    <div class="text-xs text-gray-400">prontas para uso</div>
                </div>
                <div class="stats-card-elegant slide-up" style="animation-delay: 300ms">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Inativas</div>
                    <div class="stats-value-elegant text-red-600">{{ stats.inactive }}</div>
                    <div class="text-xs text-gray-400">aguardando revisão</div>
                </div>
                <div class="stats-card-elegant slide-up" style="animation-delay: 350ms">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Dificuldade</div>
                    <div class="stats-value-elegant text-blue-600">
                        {{ calculateAverageDifficulty() }}
                    </div>
                    <div class="text-xs text-gray-400">média do banco</div>
                </div>
            </div>

            <!-- Lista de questões Elegante -->
            <div class="card-elegant hover-lift slide-up ml-mr-1" style="animation-delay: 400ms; margin-bottom: 1rem !important;">
                <!-- Cabeçalho da tabela -->
                <div class="card-header-elegant flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="card-title-elegant">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Lista de Questões
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Mostrando {{ questions.data.length }} de {{ questions.total }} questões
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">Mostrar:</span>
                            <select v-model="form.per_page"
                                    @change="applyFilters"
                                    class="form-control-elegant text-sm py-1 px-2">
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tabela Elegante -->
                <div class="overflow-x-auto">
                    <table class="table-elegant">
                        <thead>
                            <tr>
                                <th class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Enunciado
                                    </div>
                                </th>
                                <th class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                        Matéria
                                    </div>
                                </th>
                                <th class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                        Dificuldade
                                    </div>
                                </th>
                                <th class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Pontos
                                    </div>
                                </th>
                                <th class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Status
                                    </div>
                                </th>
                                <th class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Ações
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(question, index) in questions.data" :key="question.id"
                                class="fade-in" :style="`animation-delay: ${index * 50}ms`">
                                <!-- Enunciado -->
                                <td class="py-4">
                                    <div class="max-w-xs">
                                        <div class="text-sm font-medium text-gray-900 truncate hover:text-clip hover:whitespace-normal cursor-pointer group">
                                            {{ question.statement }}
                                            <div class="text-xs text-gray-500 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                {{ question.alternatives_count }} alternativa{{ question.alternatives_count !== 1 ? 's' : '' }}
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ formatDate(question.created_at) }}
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Matéria -->

                                <td v-if="question.subject != null" class="py-4">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full mr-3 flex items-center justify-center text-white font-medium text-xs shadow-sm"
                                            :style="{ backgroundColor: question.subject.color }">
                                            {{ question.subject.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ question.subject.name }}</span>
                                            <div v-if="question.topic" class="text-xs text-gray-500">
                                                {{ question.topic.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td v-else class="py-4">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full mr-3 flex items-center justify-center text-white font-medium text-xs shadow-sm"
                                            :style="{ backgroundColor: 'red' }">
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">Sem matéria</span>
                                            <div v-if="question.topic" class="text-xs text-gray-500">
                                                {{ question.topic.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Dificuldade -->
                                <td class="py-4">
                                    <span :class="[
                                        'difficulty-badge inline-flex items-center gap-1',
                                        question.difficulty_level === 'easy' ? 'difficulty-easy' :
                                        question.difficulty_level === 'medium' ? 'difficulty-medium' :
                                        'difficulty-hard'
                                    ]">
                                        <svg v-if="question.difficulty_level === 'easy'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg v-else-if="question.difficulty_level === 'medium'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L3.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        {{ question.difficulty_level === 'easy' ? 'Fácil' :
                                        question.difficulty_level === 'medium' ? 'Média' : 'Difícil' }}
                                    </span>
                                </td>
                                
                                <!-- Pontos -->
                                <td class="py-4">
                                    <div class="flex items-center">
                                        <span class="text-lg font-bold text-gray-900 mr-2">{{ question.points }}</span>
                                        <span class="text-sm text-gray-500">ponto{{ question.points !== 1 ? 's' : '' }}</span>
                                    </div>
                                </td>
                                
                                <!-- Status -->
                                <td class="py-4">
                                    <span :class="[
                                        'status-badge',
                                        question.is_active ? 'status-active' : 'status-inactive'
                                    ]">
                                        <svg v-if="question.is_active" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg v-else class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        {{ question.is_active ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </td>
                                
                                <!-- Ações -->
                                <td class="py-4">
                                    <div class="flex items-center space-x-2">
                                        <a :href="route('questions.show', question.id)"
                                        class="btn-elegant btn-elegant-outline p-1.5 text-xs"
                                        title="Visualizar questão">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a :href="route('questions.edit', question.id)"
                                        class="btn-elegant btn-elegant-outline p-1.5 text-xs"
                                        title="Editar questão">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <button @click="confirmDelete(question)"
                                                class="btn-elegant btn-elegant-outline p-1.5 text-xs text-red-600 border-red-300 hover:bg-red-50"
                                                title="Inativar questão">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação Elegante -->
                <div v-if="questions.links.length > 3" class="card-footer-elegant">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-sm text-gray-500">
                            Página <span class="font-semibold text-gray-900">{{ questions.current_page }}</span>
                            de <span class="font-semibold text-gray-900">{{ questions.last_page }}</span>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="link in questions.links">
                                <a v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                                    link.active 
                                        ? 'btn-elegant btn-elegant-primary py-1.5 px-3' 
                                        : 'btn-elegant btn-elegant-outline py-1.5 px-3'
                                ]"
                                v-html="link.label">
                                </a>
                                <span v-else
                                    class="px-3 py-1.5 text-gray-400"
                                    v-html="link.label">
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Empty State Elegante -->
                <div v-if="questions.data.length === 0" class="empty-state-elegant">
                    <div class="empty-state-icon">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="empty-state-title">Nenhuma questão encontrada</h3>
                    <p class="empty-state-description">
                        <span v-if="hasFilters">
                            Não encontramos questões com os filtros aplicados.
                        </span>
                        <span v-else>
                            Você ainda não criou nenhuma questão. Comece criando sua primeira!
                        </span>
                    </p>
                    <div class="mt-6">
                        <a v-if="hasFilters" 
                        @click="resetFilters"
                        class="btn-elegant btn-elegant-secondary mr-3">
                            Limpar Filtros
                        </a>
                        <a :href="route('questions.create')"
                        class="btn-elegant btn-elegant-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Criar Primeira Questão
                        </a>
                    </div>
                </div>
            </div>
        </div>

        

    </AppLayout>
    <Teleport to="body">
        <!-- Modal de Confirmação Elegante -->
        <div v-if="showDeleteModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] p-4 fade-in">
            <div class="card-elegant max-w-md w-full mx-4">
                <div class="flex items-center mb-4">
                    <div class="h-10 w-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L3.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar inativação</h3>
                        <!-- <p class="text-sm text-gray-500">Atenção: esta ação não pode ser desfeita</p> -->
                    </div>
                </div>
                
                <p class="text-gray-600 mb-6 p-4 bg-red-50 rounded-lg border border-red-100">
                    Tem certeza que deseja inativar a questão 
                    <span class="font-semibold">"{{ questionToDelete?.statement?.substring(0, 50) }}..."</span>?
                </p>
                
                <div class="card-footer-elegant" style="justify-content: center;">
                    <button @click="showDeleteModal = false"
                            class="btn-elegant btn-elegant-secondary">
                        Cancelar
                    </button>
                    <button @click="deleteQuestion"
                            class="btn-elegant btn-elegant-danger flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Sim, inativar questão
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
    
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import AppLayout from '@/Layouts/AppLayout.vue';
const props = defineProps({
    questions: Object,
    filters: Object,
    subjects: Array,
    topics: Array,
    question_types: Array,
    tags: Array,
    difficulty_levels: Array,
    stats: Object,
});

// Estado
const showFilters = ref(false);
const showDeleteModal = ref(false);
const questionToDelete = ref(null);

// Formulário de filtros
const form = useForm({
    search: props.filters.search || '',
    subject_id: props.filters.subject_id || '',
    topic_id: props.filters.topic_id || '',
    difficulty_level: props.filters.difficulty_level || '',
    question_type_id: props.filters.question_type_id || '',
    is_active: props.filters.is_active || '',
    per_page: props.filters.per_page || 15,
});

// Computed
const hasFilters = computed(() => {
    return Object.values(props.filters).some(value => value !== '' && value !== null);
});

// Métodos
const toggleFilterPanel = () => {
    showFilters.value = !showFilters.value;
};

const applyFilters = () => {
    form.get(route('questions.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    form.reset();
    form.get(route('questions.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const confirmDelete = (question) => {
    console.log(question);
    questionToDelete.value = question;
    showDeleteModal.value = true;
};

const deleteQuestion = () => {
    if (questionToDelete.value) {
        router.delete(route('questions.destroy', questionToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                questionToDelete.value = null;
            },
        });
    }
};

const calculateAverageDifficulty = () => {
    const weights = { easy: 1, medium: 2, hard: 3 };
    const total = props.stats.by_difficulty.easy + props.stats.by_difficulty.medium + props.stats.by_difficulty.hard;
    
    if (total === 0) return 'N/A';
    
    const average = (
        (props.stats.by_difficulty.easy * weights.easy +
         props.stats.by_difficulty.medium * weights.medium +
         props.stats.by_difficulty.hard * weights.hard) / total
    ).toFixed(1);
    
    return average <= 1.5 ? 'Fácil' : average <= 2.5 ? 'Média' : 'Difícil';
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
};

// Watch para busca com debounce
watch(
    () => form.search,
    debounce(() => {
        if (form.search !== undefined) {
            applyFilters();
        }
    }, 500)
);
</script>

<style scoped>
/* Animações em cascata para as linhas da tabela */
.fade-in {
    animation: fadeIn 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Efeito hover melhorado para as linhas */
.table-elegant tbody tr {
    transition: all 0.2s ease;
}

.table-elegant tbody tr:hover {
    background: linear-gradient(90deg, 
        rgba(59, 130, 246, 0.05) 0%,
        rgba(59, 130, 246, 0.02) 100%);
    transform: scale(1.005);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Truncate com efeito hover */
.truncate-hover {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    transition: all 0.3s ease;
}

.truncate-hover:hover {
    text-overflow: clip;
    white-space: normal;
    word-break: break-word;
}
</style>