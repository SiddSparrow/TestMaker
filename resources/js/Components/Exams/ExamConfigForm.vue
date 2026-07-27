<template>
    <component :is="mode === 'modal' ? AppModal : 'div'"
               v-bind="mode === 'modal'
                   ? { show: show, title: 'Configurações da Prova', maxWidth: '2xl' }
                   : { class: 'bg-white rounded-lg shadow-lg border border-gray-200' }"
               @close="$emit('cancel')">
        <div :class="mode === 'modal' ? 'max-h-[80vh] overflow-y-auto' : ''">
        <form @submit.prevent="submitConfig">
        <div :class="mode === 'modal' ? 'space-y-6' : 'p-6 space-y-6'">
            <div v-if="mode === 'inline'">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Nova Prova</h2>
                <p class="text-sm text-gray-600">Preencha o essencial para começar a montar a prova — o resto é opcional e pode ser ajustado depois.</p>
            </div>
            <p v-else class="text-sm text-gray-600">Edite as informações e configurações da prova.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormField class="md:col-span-2" label="Título da Prova" required :error="errors.title" v-slot="{ id }">
                    <input :id="id" ref="titleInputRef" type="text"
                           v-model="localConfig.title"
                           :autofocus="mode === 'inline'"
                           placeholder="Ex: Prova de Matemática - 1º Bimestre"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                           :class="{ 'border-red-500': errors.title }">
                </FormField>

                <FormField label="Matéria Principal" required :error="errors.main_subject_id" v-slot="{ id }">
                    <select :id="id" ref="mainSubjectSelectRef" v-model="localConfig.main_subject_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            :class="{ 'border-red-500': errors.main_subject_id }">
                        <option value="">Selecione a matéria</option>
                        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                            {{ subject.name }}
                        </option>
                    </select>
                </FormField>

                <FormField label="Data da Prova" v-slot="{ id }">
                    <input :id="id" type="date"
                           v-model="localConfig.exam_date"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </FormField>

                <FormField class="md:col-span-2" label="Descrição/Instruções" v-slot="{ id }">
                    <textarea :id="id"
                              v-model="localConfig.description"
                              rows="3"
                              placeholder="Adicione instruções ou observações para os alunos..."
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                </FormField>
            </div>

            <!-- Configurações avançadas — antes eram 3 passos obrigatórios do
                 wizard (config de pontos/questões, tópicos, layout) que o
                 builder na prática ignorava quase por completo. Viram um
                 bloco opcional recolhido por padrão. -->
            <details class="border border-gray-200 rounded-lg" :open="mode === 'modal'" @toggle="advancedOpen = $event.target.open">
                <summary class="cursor-pointer select-none px-4 py-3 font-medium text-gray-700 hover:bg-gray-50 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': advancedOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    Configurações avançadas (opcional)
                </summary>

                <div class="p-5 space-y-6 border-t border-gray-200">
                    <!-- Metas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField label="Pontuação Total Desejada" hint="Deixe vazio para calcular automaticamente" v-slot="{ id }">
                            <div class="relative">
                                <input :id="id" type="number"
                                       v-model.number="localConfig.target_total_points"
                                       min="1"
                                       placeholder="100"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">pontos</span>
                            </div>
                        </FormField>

                        <FormField label="Quantidade de Questões" hint="Quantidade ideal de questões" v-slot="{ id }">
                            <input :id="id" type="number"
                                   v-model.number="localConfig.target_question_count"
                                   min="1"
                                   placeholder="10"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </FormField>
                    </div>

                    <!-- Distribuição por Dificuldade -->
                    <div>
                        <span :id="ids.difficulty" class="block text-sm font-medium text-gray-700 mb-3">
                            Distribuição por Dificuldade (opcional)
                        </span>
                        <div class="grid grid-cols-3 gap-4" role="group" :aria-labelledby="ids.difficulty">
                            <div class="p-4 border-2 rounded-lg transition-all"
                                 :class="localConfig.difficulty_distribution.easy > 0 ? 'border-green-500 bg-green-50' : 'border-gray-200'">
                                <label :for="ids.difficultyEasy" class="block text-sm font-medium text-green-700 mb-2">Fácil</label>
                                <input :id="ids.difficultyEasy" type="number"
                                       v-model.number="localConfig.difficulty_distribution.easy"
                                       min="0"
                                       placeholder="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                                <p class="mt-1 text-xs text-gray-600">questões</p>
                            </div>

                            <div class="p-4 border-2 rounded-lg transition-all"
                                 :class="localConfig.difficulty_distribution.medium > 0 ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200'">
                                <label :for="ids.difficultyMedium" class="block text-sm font-medium text-yellow-700 mb-2">Médio</label>
                                <input :id="ids.difficultyMedium" type="number"
                                       v-model.number="localConfig.difficulty_distribution.medium"
                                       min="0"
                                       placeholder="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-yellow-500 focus:border-yellow-500">
                                <p class="mt-1 text-xs text-gray-600">questões</p>
                            </div>

                            <div class="p-4 border-2 rounded-lg transition-all"
                                 :class="localConfig.difficulty_distribution.hard > 0 ? 'border-red-500 bg-red-50' : 'border-gray-200'">
                                <label :for="ids.difficultyHard" class="block text-sm font-medium text-red-700 mb-2">Difícil</label>
                                <input :id="ids.difficultyHard" type="number"
                                       v-model.number="localConfig.difficulty_distribution.hard"
                                       min="0"
                                       placeholder="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500">
                                <p class="mt-1 text-xs text-gray-600">questões</p>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">
                            Total configurado: <span class="font-semibold">{{ totalDifficultyCount }}</span> questões
                        </p>
                    </div>

                    <!-- Distribuição por Tópicos -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Distribuição por Tópicos (opcional)</h3>

                        <div v-if="availableTopics.length > 0" class="space-y-3">
                            <div v-for="(topicConfig, index) in localConfig.topic_distribution"
                                 :key="index"
                                 class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <label :for="`topic-${index}`" class="sr-only">Tópico</label>
                                        <select :id="`topic-${index}`" v-model="topicConfig.topic_id"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Selecione um tópico</option>
                                            <option v-for="topic in getUnselectedTopics(index)"
                                                    :key="topic.id"
                                                    :value="topic.id">
                                                {{ topic.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="w-32">
                                        <label :for="`topic-count-${index}`" class="sr-only">Quantidade</label>
                                        <input :id="`topic-count-${index}`" type="number"
                                               v-model.number="topicConfig.question_count"
                                               min="1"
                                               placeholder="Qtd"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <button @click="removeTopicDistribution(index)"
                                            type="button"
                                            aria-label="Remover distribuição de tópico"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <button @click="addTopicDistribution"
                                    type="button"
                                    class="w-full px-4 py-2.5 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-colors">
                                + Adicionar Tópico
                            </button>

                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-sm text-blue-800">
                                    <strong>Total de questões por tópicos:</strong> {{ totalTopicCount }}
                                </p>
                            </div>
                        </div>

                        <p v-else class="text-sm text-gray-500">Selecione uma matéria com tópicos cadastrados para distribuir questões por tópico.</p>
                    </div>

                    <!-- Cabeçalho -->
                    <div class="space-y-4 p-5 bg-gray-50 border border-gray-200 rounded-lg">
                        <h3 class="text-sm font-semibold text-gray-900">Cabeçalho</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <FormField label="Nome da Escola/Instituição" v-slot="{ id }">
                                <input :id="id" type="text"
                                       v-model="localConfig.header_config.school_name"
                                       placeholder="Ex: Colégio ABC"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </FormField>

                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                                    <input type="checkbox" v-model="localConfig.header_config.show_date"
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-700">Mostrar data da prova</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                                    <input type="checkbox" v-model="localConfig.header_config.show_student_info"
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-700">Campos para nome/turma do aluno</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                                    <input type="checkbox" v-model="localConfig.header_config.show_logo"
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-700">Espaço para logo da escola</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Formatação de Texto -->
                    <div class="space-y-4 p-5 bg-gray-50 border border-gray-200 rounded-lg">
                        <h3 class="text-sm font-semibold text-gray-900">Formatação de Texto</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <FormField label="Tamanho da Fonte" v-slot="{ id }">
                                <select :id="id" v-model="localConfig.format_config.font_size"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="10pt">10pt (Pequeno)</option>
                                    <option value="11pt">11pt (Normal)</option>
                                    <option value="12pt">12pt (Médio)</option>
                                    <option value="14pt">14pt (Grande)</option>
                                </select>
                            </FormField>

                            <FormField label="Família da Fonte" v-slot="{ id }">
                                <select :id="id" v-model="localConfig.format_config.font_family"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Calibri">Calibri</option>
                                    <option value="Georgia">Georgia</option>
                                </select>
                            </FormField>

                            <FormField label="Espaçamento entre Linhas" v-slot="{ id }">
                                <select :id="id" v-model="localConfig.format_config.line_spacing"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="1.0">Simples (1.0)</option>
                                    <option value="1.15">1.15</option>
                                    <option value="1.5">1.5</option>
                                    <option value="2.0">Duplo (2.0)</option>
                                </select>
                            </FormField>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                            <input type="checkbox" v-model="localConfig.format_config.justify_text"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Justificar texto das questões</span>
                        </label>
                    </div>

                    <!-- Layout da Página -->
                    <div class="space-y-4 p-5 bg-gray-50 border border-gray-200 rounded-lg">
                        <h3 class="text-sm font-semibold text-gray-900">Layout da Página</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-2">Número de Colunas</span>
                                <div class="grid grid-cols-2 gap-2" role="group" aria-label="Número de colunas">
                                    <button type="button"
                                            @click="localConfig.format_config.columns = 1"
                                            :aria-pressed="localConfig.format_config.columns === 1"
                                            :class="localConfig.format_config.columns === 1 ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                                            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-blue-50 transition-colors">
                                        1 Coluna
                                    </button>
                                    <button type="button"
                                            @click="localConfig.format_config.columns = 2"
                                            :aria-pressed="localConfig.format_config.columns === 2"
                                            :class="localConfig.format_config.columns === 2 ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                                            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-blue-50 transition-colors">
                                        2 Colunas
                                    </button>
                                </div>
                            </div>

                            <FormField label="Tamanho das Margens" v-slot="{ id }">
                                <select :id="id" v-model="localConfig.format_config.margins"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="narrow">Estreitas (1.27cm)</option>
                                    <option value="normal">Normais (2.5cm)</option>
                                    <option value="wide">Largas (3.17cm)</option>
                                </select>
                            </FormField>

                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-2">Orientação da Página</span>
                                <div class="grid grid-cols-2 gap-2" role="group" aria-label="Orientação da página">
                                    <button type="button"
                                            @click="localConfig.format_config.orientation = 'portrait'"
                                            :aria-pressed="localConfig.format_config.orientation === 'portrait'"
                                            :class="localConfig.format_config.orientation === 'portrait' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                                            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-blue-50 transition-colors">
                                        Retrato
                                    </button>
                                    <button type="button"
                                            @click="localConfig.format_config.orientation = 'landscape'"
                                            :aria-pressed="localConfig.format_config.orientation === 'landscape'"
                                            :class="localConfig.format_config.orientation === 'landscape' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                                            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-blue-50 transition-colors">
                                        Paisagem
                                    </button>
                                </div>
                            </div>

                            <FormField label="Tamanho do Papel" v-slot="{ id }">
                                <select :id="id" v-model="localConfig.format_config.paper_size"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="A4">A4 (21 x 29.7 cm)</option>
                                    <option value="Letter">Carta (21.6 x 27.9 cm)</option>
                                </select>
                            </FormField>
                        </div>
                    </div>

                    <!-- Opções de Questões -->
                    <div class="space-y-2 p-5 bg-gray-50 border border-gray-200 rounded-lg">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Opções de Questões</h3>

                        <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                            <input type="checkbox" v-model="localConfig.format_config.show_question_points"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Mostrar pontuação de cada questão</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                            <input type="checkbox" v-model="localConfig.format_config.shuffle_questions"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Embaralhar ordem das questões</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                            <input type="checkbox" v-model="localConfig.format_config.shuffle_alternatives"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Embaralhar alternativas (múltipla escolha)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                            <input type="checkbox" v-model="localConfig.format_config.show_answer_space"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Adicionar espaço para resposta (questões dissertativas)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                            <input type="checkbox" v-model="localConfig.format_config.separate_answer_sheet"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Gerar folha de respostas separada (gabarito)</span>
                        </label>
                    </div>

                    <!-- Rodapé -->
                    <div class="space-y-4 p-5 bg-gray-50 border border-gray-200 rounded-lg">
                        <h3 class="text-sm font-semibold text-gray-900">Rodapé</h3>

                        <FormField label="Texto Personalizado" v-slot="{ id }">
                            <input :id="id" type="text"
                                   v-model="localConfig.footer_config.custom_text"
                                   placeholder="Ex: Boa prova!"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </FormField>

                        <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded">
                            <input type="checkbox" v-model="localConfig.footer_config.show_page_number"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Mostrar numeração de páginas</span>
                        </label>
                    </div>
                </div>
            </details>
        </div>

        <!-- Ações -->
        <div :class="mode === 'inline'
            ? 'px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-end gap-3'
            : 'pt-6 mt-6 border-t border-gray-200 flex items-center justify-end gap-3'">
            <button @click="$emit('cancel')" type="button"
                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                Cancelar
            </button>
            <BaseButton type="submit">{{ mode === 'inline' ? 'Começar a Montar Prova' : 'Salvar Alterações' }}</BaseButton>
        </div>
        </form>
        </div>
    </component>
</template>

<script setup>
import { ref, computed, watch, useId } from 'vue';
import FormField from '@/Components/UI/FormField.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import AppModal from '@/Components/UI/AppModal.vue';

const props = defineProps({
    mode: {
        type: String,
        default: 'inline', // 'inline' (criação) ou 'modal' (edição, via AppModal)
        validator: (value) => ['inline', 'modal'].includes(value)
    },
    show: {
        type: Boolean,
        default: true
    },
    modelValue: {
        type: Object,
        default: () => ({})
    },
    subjects: {
        type: Array,
        default: () => []
    },
    topics: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue', 'complete', 'cancel']);

const uid = useId();
const ids = {
    difficulty: `difficulty-group-${uid}`,
    difficultyEasy: `difficulty-easy-${uid}`,
    difficultyMedium: `difficulty-medium-${uid}`,
    difficultyHard: `difficulty-hard-${uid}`,
};

const advancedOpen = ref(props.mode === 'modal');
const errors = ref({});
const titleInputRef = ref(null);
const mainSubjectSelectRef = ref(null);

const defaultConfig = () => ({
    title: '',
    description: '',
    exam_date: '',
    main_subject_id: '',
    target_total_points: null,
    target_question_count: null,
    difficulty_distribution: {
        easy: 0,
        medium: 0,
        hard: 0
    },
    topic_distribution: [],
    header_config: {
        school_name: '',
        show_date: true,
        show_student_info: true,
        show_logo: false
    },
    format_config: {
        font_size: '12pt',
        font_family: 'Arial',
        line_spacing: '1.5',
        justify_text: false,
        columns: 1,
        margins: 'normal',
        orientation: 'portrait',
        paper_size: 'A4',
        show_question_points: true,
        shuffle_questions: false,
        shuffle_alternatives: false,
        show_answer_space: true,
        separate_answer_sheet: false
    },
    footer_config: {
        custom_text: 'Boa prova!',
        show_page_number: true
    }
});

// exam_date chega do backend em ISO completo (ex.: "2026-01-07T00:00:00.000000Z",
// serialização padrão do Carbon), mas <input type="date"> só aceita
// exatamente "yyyy-MM-dd" — atribuir o valor bruto faz o navegador rejeitar
// silenciosamente (aviso no console, campo fica vazio).
const toDateInputValue = (value) => (value ? String(value).slice(0, 10) : '');

const buildLocalConfig = () => ({
    ...defaultConfig(),
    ...props.modelValue,
    exam_date: toDateInputValue(props.modelValue?.exam_date),
});

const localConfig = ref(buildLocalConfig());

// No modo modal, os dados de origem (props.modelValue = exam) só existem
// quando o modal é reaberto — reinicializar o formulário toda vez que ele
// abre, igual ao comportamento antigo do ExamConfigEditModal.
if (props.mode === 'modal') {
    watch(() => props.show, (isOpen) => {
        if (isOpen) {
            localConfig.value = buildLocalConfig();
            errors.value = {};
        }
    });
}

const availableTopics = computed(() => {
    if (!localConfig.value.main_subject_id) return [];
    return props.topics.filter(t => t.subject_id == localConfig.value.main_subject_id);
});

const totalDifficultyCount = computed(() => {
    const dist = localConfig.value.difficulty_distribution;
    return (dist.easy || 0) + (dist.medium || 0) + (dist.hard || 0);
});

const totalTopicCount = computed(() => {
    return localConfig.value.topic_distribution.reduce((sum, t) => sum + (t.question_count || 0), 0);
});

watch(localConfig, (newVal) => {
    emit('update:modelValue', newVal);
}, { deep: true });

const validate = () => {
    errors.value = {};

    if (!localConfig.value.title) {
        errors.value.title = 'O título é obrigatório';
    }
    if (!localConfig.value.main_subject_id) {
        errors.value.main_subject_id = 'Selecione uma matéria';
    }

    return Object.keys(errors.value).length === 0;
};

const submitConfig = () => {
    if (validate()) {
        emit('complete', localConfig.value);
        return;
    }

    // Sem isto, um erro no bloco de configurações avançadas (fechado por
    // padrão no modo inline) ficava fora da viewport e o usuário nem via
    // por que o botão de confirmação não fez nada.
    if (errors.value.title) {
        titleInputRef.value?.focus();
    } else if (errors.value.main_subject_id) {
        mainSubjectSelectRef.value?.focus();
    }
};

const addTopicDistribution = () => {
    localConfig.value.topic_distribution.push({
        topic_id: '',
        question_count: 1
    });
};

const removeTopicDistribution = (index) => {
    localConfig.value.topic_distribution.splice(index, 1);
};

const getUnselectedTopics = (currentIndex) => {
    const selectedIds = localConfig.value.topic_distribution
        .map((t, i) => i !== currentIndex ? t.topic_id : null)
        .filter(id => id);

    return availableTopics.value.filter(t => !selectedIds.includes(t.id));
};
</script>
