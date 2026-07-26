# Auditoria de UX/UI — TestMaker (estado pós-refatoração)

**Data:** 26/07/2026
**Escopo:** 19 páginas Inertia (`resources/js/Pages/**`), 36 componentes (`resources/js/Components/**`), 2 layouts (`resources/js/Layouts/**`), 9 controllers (`app/Http/Controllers/**`), rotas, `tailwind.config.js`, `resources/css/app.css`, `package.json`.
**Método:** leitura estática do código no estado atual (HEAD = `a53bf8f`). Nada foi executado no browser.
**Relação com a auditoria anterior:** `docs/auditoria-ux.md` descreve o estado *anterior*. Aqui ele é usado apenas como checklist de verificação (seção 3); todo o resto é diagnóstico novo e independente.

> **Limitação declarada:** responsividade, contraste real, comportamento de foco e boa parte dos problemas de percepção só podem ser *inferidos* do código. Os itens marcados *(inferido)* precisam de validação no browser. Onde há evidência estrutural forte (dimensão fixa em cm, ausência de breakpoint, `position: fixed` empilhado), isso está explicitado.

---

## 1. Árvore de navegação e telas mapeadas

```
/ (redirect) → /dashboard
│
├── AppLayout (barra persistente: Dashboard · Questões · Provas · Documentos · Cadastros▾ · [usuário]▾)
│   ├── /dashboard ................. Dashboard.vue
│   ├── /questions ................. Questions/Index.vue
│   │   ├── /questions/create ...... Questions/Create.vue
│   │   ├── /questions/{id} ........ Questions/Show.vue
│   │   └── /questions/{id}/edit ... Questions/Edit.vue
│   ├── /exams ..................... Exams/Index.vue
│   │   ├── /exams/create .......... Exams/Create.vue      (ExamConfigForm → ExamBuilder)
│   │   ├── /exams/{id} ............ Exams/Show.vue
│   │   └── /exams/{id}/edit ....... Exams/Edit.vue        (ExamBuilder + ExamConfigEditModal)
│   ├── /documents ................. Documents/Index.vue
│   │   ├── /documents/create ...... Documents/Create.vue
│   │   └── /documents/{id} ........ Documents/Show.vue    (revisão da extração por IA)
│   ├── Cadastros▾
│   │   ├── /subjects .............. Subjects/Index.vue
│   │   ├── /topics ................ Topics/Index.vue
│   │   └── /tags .................. Tags/Index.vue
│   └── /profile ................... Profile/Edit.vue      (3 partials)
│
└── AuthLayout
    ├── /login · /register · /forgot-password · /reset-password/{token}
    └── /confirm-password · /verify-email
```

| # | Tela | Arquivo | Função | Layout | Cabeçalho | Tabela | Paginação | Filtros | Vazio | Loading |
|---|------|---------|--------|--------|-----------|--------|-----------|---------|-------|---------|
| 1 | Dashboard | `Pages/Dashboard.vue` | Hub + KPIs | AppLayout | **sem `<h1>`** | — | — | — | parcial | ✗ |
| 2 | Questões | `Pages/Questions/Index.vue` | Banco de questões | AppLayout | PageHeader | DataTable | ✓ servidor | 4 campos + per_page | EmptyState | ✓ skeleton |
| 3 | Nova Questão | `Pages/Questions/Create.vue` | Formulário | AppLayout | `h1` ad-hoc (text-3xl) | — | — | — | — | ✓ botão |
| 4 | Editar Questão | `Pages/Questions/Edit.vue` | Formulário + cópia | AppLayout | `h1` ad-hoc (text-3xl) | — | — | — | — | ✓ botão |
| 5 | Ver Questão | `Pages/Questions/Show.vue` | Leitura | AppLayout | `h1` ad-hoc (text-3xl) | — | — | — | — | ✗ |
| 6 | Provas | `Pages/Exams/Index.vue` | Lista de provas | AppLayout | PageHeader | DataTable | ✓ servidor | busca (sem rótulo) | EmptyState | ✓ skeleton |
| 7 | Nova Prova | `Pages/Exams/Create.vue` | Config + builder | AppLayout | `h1` só na 2ª etapa | — | — | — | ad-hoc | ✗ |
| 8 | Editar Prova | `Pages/Exams/Edit.vue` | Builder | AppLayout | header gradiente próprio | — | — | — | ad-hoc | parcial |
| 9 | Ver Prova | `Pages/Exams/Show.vue` | Detalhe + export | AppLayout | header gradiente próprio | lista | — | — | ad-hoc | ✗ |
| 10 | Documentos | `Pages/Documents/Index.vue` | Lista de uploads | AppLayout | PageHeader | DataTable | ✓ servidor | busca + status | EmptyState | ✓ skeleton |
| 11 | Upload | `Pages/Documents/Create.vue` | Drag & drop | AppLayout | `h1` ad-hoc (text-2xl) | — | — | — | — | ✓ botão |
| 12 | Revisar extração | `Pages/Documents/Show.vue` | Edição em massa | AppLayout | `h1` ad-hoc (text-2xl) | cards | **quebrada** (§4.2) | — | ✓ | ✓ estado |
| 13 | Matérias | `Pages/Subjects/Index.vue` | CRUD catálogo | AppLayout | PageHeader | lista | ✗ | **nenhum** | EmptyState | ✗ |
| 14 | Tópicos | `Pages/Topics/Index.vue` | CRUD catálogo | AppLayout | PageHeader | lista | ✗ | por matéria (client) | EmptyState | ✗ |
| 15 | Etiquetas | `Pages/Tags/Index.vue` | CRUD catálogo | AppLayout | PageHeader | grid | ✗ | busca (client) | EmptyState | ✗ |
| 16 | Perfil | `Pages/Profile/Edit.vue` | Conta | AppLayout **`#header`** | `h2` (único caso) | — | — | — | — | ✓ |
| 17–22 | Auth (6 telas) | `Pages/Auth/*` | Autenticação | AuthLayout | `h2` no layout | — | — | — | — | ✓ |

Observações estruturais:
- **Todas as 19 telas têm `<Head :title>`** e o `app.js` monta `título · TestMaker`.
- **Nenhuma tela órfã.** Não existe mais `Welcome.vue`, `AuthenticatedLayout`, nem os 3 `ziggy.js` estáticos.
- **Rotas órfãs:** `questions.copy` (`routes/web.php:40`) e `questions.destroy` não têm consumidor na UI (a cópia passa por `questions.store` com `copy_from_id`; o arquivamento passa por `questions.bulk-status`).

---

## 2. Resumo executivo — 12 problemas mais críticos

| # | Problema | Evidência | Impacto no usuário | Prio |
|---|----------|-----------|--------------------|------|
| 1 | **A paginação em lotes da revisão de extração não funciona.** `currentPage` é inicializado em `1` e **nunca é reatribuído**; os "links" são `#1`/`#prev`/`#next` entregues ao `Pagination`, que os renderiza como `<Link>` do Inertia — clicar dispara uma visita que **descarta as edições locais** e não muda o lote. | `Pages/Documents/Show.vue:301,322,329-337` + `Components/UI/Pagination.vue:12-24` | Documento com 30 questões extraídas: só as 10 primeiras podem ser revisadas/corrigidas, e as 30 são importadas assim mesmo (todas vêm pré-selecionadas, `Show.vue:317`). Fluxo principal do produto quebrado. | **Alta** |
| 2 | **`onSubjectChange` não existe.** Referenciado no template de duas telas, não definido em nenhum `<script setup>`. | `Pages/Questions/Create.vue:33` e `Pages/Questions/Edit.vue:85` (`grep` acha só os 2 usos, 0 definições) | Warning do Vue em dev e handler morto: o evento `subject-change` emitido em `QuestionFormFields.vue:357` não é tratado. | **Alta** |
| 3 | **Toasts simultâneos se sobrepõem.** O host empilha com `space-y-3`, mas cada toast tem raiz `fixed top-4 right-4` — `space-y` não afeta filhos posicionados. | `Components/UI/ToastHost.vue:11` vs `Components/ToastNotification.vue:4-5` | Duas mensagens (ex.: import parcial → `success` + `error`) ficam exatamente uma sobre a outra. | **Alta** |
| 4 | **`confirm()`/`alert()` nativos sobreviveram em Provas.** `Exams/Show` é a única tela que exclui sem `ConfirmDialog`. | `Pages/Exams/Show.vue:375`, `Pages/Exams/Create.vue:254` | "Excluir Prova" tem 2 aparências: modal desenhado (Index/Edit) e caixa do SO (Show). | **Alta** |
| 5 | **`AppModal` tem 0 usos.** Convivem 4 padrões de modal: `ConfirmDialog`, `Modal` cru (`DeleteUserForm`), `ExamPreview` e `ExamConfigEditModal` (cada um com header/close próprios). | `Components/UI/AppModal.vue` (nenhum import externo) | Componente morto e a inconsistência que ele deveria eliminar permanece. | **Alta** |
| 6 | **`CopySuccessBanner` (414 linhas, CSS próprio) é inalcançável.** `Questions/Edit.vue` lê `props.is_copy`/`props.original_question_id`, **não declarados em `defineProps` nem enviados por `QuestionController@edit`**. | `Pages/Questions/Edit.vue:207-213,228-229` | Depois de duplicar, o usuário nunca vê de qual questão a cópia veio. | **Alta** |
| 7 | **Card "Questões Ativas" do Dashboard mostra o total, incluindo arquivadas.** | `DashboardController.php:22` vs `Questions/Index.vue:88-95` (que separa total/ativas/inativas) | Dois números diferentes para a mesma coisa em duas telas; o KPI mente sobre o próprio rótulo. | **Alta** |
| 8 | **O diálogo de exclusão de matéria promete o oposto do que o backend faz.** A mensagem diz que as questões "serão mantidas, mas não vinculadas"; o controller **recusa** a exclusão se houver questões. | `Pages/Subjects/Index.vue:139-142` vs `SubjectController.php:56-63` | O usuário confirma esperando uma coisa e recebe um toast de erro. | **Alta** |
| 9 | **Publicar/despublicar prova não tem estado visível.** A coluna "Status" deriva só de `exam_date` e ignora `is_published`; só o `aria-label` do botão muda. | `Pages/Exams/Index.vue:117-124,336-347` + `ExamController@togglePublish` | Clica, recebe o toast, e a tabela continua idêntica — impossível saber o que está publicado. | **Alta** |
| 10 | **"Limpar Filtros" é no-op quando a tela abre já filtrada.** `form.reset()` volta aos valores iniciais do `useForm`, inicializados **com `props.filters`**. | `Pages/Questions/Index.vue:290-300,318-321`; `Pages/Documents/Index.vue:196-204` | Ao recarregar/compartilhar uma URL filtrada, o botão (inclusive o do `EmptyState`) não limpa nada. | **Média-Alta** |
| 11 | **Gabarito do preview depende de ID mágico:** `question_type_id === 1` é assumido como "múltipla escolha". | `Components/Exams/ExamPreview.vue:396` | Mudar a ordem do seeder faz a folha de gabarito sumir ou listar questões erradas. | **Média-Alta** |
| 12 | **Dependências declaradas divergem do código.** `lodash-es` é importado em 3 telas e **não está no `package.json`** (só existe transitivamente); `vue` é `^3.4.0` mas `useId()` (4 arquivos) exige 3.5+; `@inertiajs/vue3` está em `dependencies` **e** `devDependencies` com versões diferentes; `@tailwindcss/vite ^4` com `tailwindcss ^3.2.1`. | `package.json` vs `Questions/Index.vue:246`, `Exams/Index.vue:190`, `Documents/Index.vue:163`, `Components/UI/FormField.vue:31` | Um `npm ci` limpo em CI/outra máquina pode gerar build quebrado ou `useId is not a function`. | **Alta (risco)** |

---

## 3. Verificação item-a-item da auditoria anterior

Legenda: ✅ resolvido (verificado no código) · ⚠️ parcial · ❌ não resolvido · ➖ não se aplica mais.

### 3.1 Os 10 problemas críticos do resumo executivo antigo

| # antigo | Problema | Status | Evidência atual |
|---|---|---|---|
| 1 | Não existe menu de navegação | ✅ | `Layouts/AppLayout.vue:36-77` — 4 itens + dropdown "Cadastros" + menu mobile (`:120-160`); `AuthenticatedLayout` foi excluído |
| 2 | Mensagens de sucesso/erro descartadas (`flash` não compartilhado) | ✅ | `HandleInertiaRequests.php:36-43` compartilha `success/error/warning/info`; `Components/UI/ToastHost.vue:31-45` observa e dispara; host montado em `AppLayout.vue:211` |
| 3 | Tela de Perfil quebrada (`route('subjects.index')` inexistente) | ✅ | Rota existe (`routes/web.php:27`); `Profile/Edit.vue:2` usa `AppLayout` |
| 4 | Listagem de Provas sem paginação, carregando tudo | ✅ | `ExamController@index:36-40,71` — sem eager-load de `questions`, `paginate()->withQueryString()` |
| 5 | Preview de prova quebrado (`await router.get`) | ✅ | `Exams/Index.vue:352-360` usa `window.axios.get`; endpoint JSON em `ExamController@questions:143-159` |
| 6 | Builder só drag & drop, `grid-cols-12` sem breakpoint | ✅ | `ExamBuilder.vue:2` (`grid-cols-1 lg:grid-cols-12`), botão "+" (`:100-108`) e mover ↑/↓ (`:222-240`) |
| 7 | 3 padrões de tabela e 4 de confirmação destrutiva | ⚠️ | Tabelas: unificadas em `DataTable` nas 3 listagens ✅. Confirmações: `ConfirmDialog` em 8 telas, mas `confirm()` sobrevive em `Exams/Show.vue:375` e `alert()` em `Exams/Create.vue:254` ❌ |
| 8 | `StatCard` sem prop `link` (cards mortos) | ✅ | `Components/StatCard.vue:37-40` declara `link` e renderiza `<Link>`; os 6 cards do Dashboard navegam |
| 9 | Configuração da prova é "teatro" (4 passos ignorados) | ⚠️ | Virou 1 passo + `<details>` opcional ✅, mas `difficulty_distribution`/`topic_distribution`/`target_question_count` continuam coletados, persistidos (`ExamController@store:104-108`) e **ignorados pelo builder**, que só consome `target_total_points` (`ExamBuilder.vue:284-291`) ❌ |
| 10 | Título do navegador sempre "TestMaker" | ✅ | `app.js:11` (`${title} · ${appName}`) + `<Head :title>` em 19/19 telas |

### 3.2 Navegação, telas redundantes e órfãs (§3.1 antigo)

| Problema antigo | Status | Evidência atual |
|---|---|---|
| Dois layouts autenticados com cromo diferente | ✅ | Só `AppLayout` e `AuthLayout`; `AuthenticatedLayout.vue` não existe |
| Matérias/Tópicos/Etiquetas só como modais do Dashboard | ✅ | `Subjects/Index.vue`, `Topics/Index.vue`, `Tags/Index.vue` + criação inline no formulário de questão (`QuestionFormFields.vue:364-413`) |
| `Welcome.vue` inalcançável | ✅ | Arquivo removido |
| 4 componentes nunca importados (`AuthCard`, `Checkbox`, `DashboardCard`, `ToastNotification`) | ⚠️ | Os 3 primeiros foram removidos e o `ToastNotification` foi adotado ✅ — mas surgiram **dois novos órfãos**: `Components/UI/AppModal.vue` (0 imports) e, na prática, `CopySuccessBanner.vue` (props nunca enviadas) ❌ |
| 3 arquivos `ziggy.js` estáticos desatualizados | ✅ | Nenhum dos 3 existe; `@routes` continua em `app.blade.php:15` |
| Dashboard calcula `most_used_subjects`/`total_documents` e não exibe | ✅ | `Dashboard.vue:41-48,126-150` |

### 3.3 Tabelas (§3.2 antigo)

| Problema antigo | Status | Evidência atual |
|---|---|---|
| Sem paginação em Provas e na revisão de documentos | ⚠️ | Provas: ✅ (`ExamController@index:71`). Revisão de documentos: existe uma paginação em lotes de 10 que **não funciona** (§2.1) ❌ |
| Busca client-side/server-side/inexistente conforme a tela | ✅ | As 3 listagens usam filtro server-side com `debounce` de 500ms (`Questions/Index.vue:347-352`, `Exams/Index.vue:370`, `Documents/Index.vue:206`) |
| Nenhuma tabela permite ordenar nem selecionar em lote | ⚠️ | Ordenação: ✅ em Questões e Provas; ❌ ausente em Documentos. Seleção em lote: ✅ só em Questões (`DataTable` `selectable` + `bulk-status`) |
| Paginação com `<a href>` e 3 aparências | ✅ | `Components/UI/Pagination.vue` com `<Link preserve-scroll preserve-state>`, usado por Questões/Provas/Documentos |
| Estado vazio com 4 tratamentos | ✅ | `EmptyState` em 6 telas (3 listagens + 3 catálogos) — resta marcação ad-hoc em `ExamBuilder.vue:252-262`, `Exams/Show.vue:216-232` e `Documents/Show.vue:...` ⚠️ |
| `hasFilters` contando `per_page` | ✅ | `Questions/Index.vue:283-289` ignora `per_page/sort/direction` |
| Nenhum estado de carregamento nas tabelas | ✅ | `DataTable.vue:63-70` (skeleton) + `router.on('start'/'finish')` — com a ressalva do listener global (§4.2) |
| Ações de linha só com ícone, sem nome acessível | ✅ | `aria-label` descritivo em todas as ações das 3 listagens e dos 3 catálogos; `BaseButton` até avisa em dev quando falta (`BaseButton.vue:36-39`) |
| Ações de Documentos como links de texto coloridos | ✅ | `Documents/Index.vue:97-131` usa `BaseButton` icon-only, igual às outras |
| Cache de 30 min sobre a listagem paginada com invalidação no-op | ⚠️ | A listagem deixou de ser cacheada (`QuestionController@index:63-70`) e `clearQuestionCache:640-650` limpa chaves escopadas por usuário ✅ — mas os **outros 5 caches de 30 min** continuam sem invalidação por parte dos catálogos e da importação de documentos, reproduzindo o mesmo sintoma (§4.11) |

### 3.4 Responsividade (§3.3 antigo)

| Problema antigo | Status | Evidência atual |
|---|---|---|
| Builder `grid-cols-12` sem breakpoint | ✅ | `ExamBuilder.vue:2,4,138` (`lg:col-span-5` / `lg:col-span-7`) |
| Preview A4 fixo sem classes responsivas | ⚠️ | Auto-zoom `fitToContainer()` (`ExamPreview.vue:455-470`) resolve o overflow inicial; a página segue com `width: 21cm` fixo e o recálculo só ocorre em mount/resize *(inferido — validar)* |
| `grid-cols-4` fixo no header de edição de prova | ✅ | `Exams/Edit.vue:57` (`grid-cols-2 md:grid-cols-4`); o `safelist` do `tailwind.config.js` também sumiu |
| `.btn-elegant{width:100%}` em <640px quebrando a coluna de ações | ➖ | `theme.css` não existe mais |
| `.table-elegant{display:block}` em <640px | ➖ | idem; `DataTable` usa `overflow-x-auto` no wrapper (`DataTable.vue:17`) |
| `max-h-[600px]`/`min-h-[600px]` no builder | ⚠️ | Viraram `max-h-[70vh]`/`min-h-[50vh]` (`ExamBuilder.vue:44,167`) — relativo à viewport, mas o scroll aninhado permanece |
| `style="margin-top: 2rem"` nos filtros | ✅ | `Questions/Index.vue:65` usa `flex items-end gap-2` |
| 45 `style="…"` inline (majoritariamente `animation-delay`) | ✅ | Restam 15 ocorrências, 13 delas legítimas (`:style` com cor vinda do banco, zoom do preview, barra de progresso). As animações viraram classes (`app.css:44-62`) com `prefers-reduced-motion` (`app.css:64-69`). Sobrou 1 impróprio: `style="border-radius: 10px"` (`Exams/Show.vue:7`) |

### 3.5 Formulários (§3.4 antigo)

| Problema antigo | Status | Evidência atual |
|---|---|---|
| Resumo de erros com nomes técnicos de campo | ✅ | `utils/fieldLabels.js` + `humanizeField()` em `Questions/Create.vue:11` e `Edit.vue:12` (inclusive para `alternatives.0.content`) |
| `<label>` sem `for`/`id` em quase todos os formulários | ⚠️ | Resolvido em `FormField` (9 telas) e em `QuestionFormFields.vue:9,54,101,184,210` (ids via `useId`). **Continua faltando** em `ExamConfigEditModal.vue:23-90` (8 rótulos sem `for`) e nas textareas de alternativa (`AlternativesManager.vue:528`) |
| Dificuldade com 3 botões sem `role`/`aria` | ⚠️ | `QuestionFormFields.vue:128-174` tem `role="radiogroup"`, `aria-labelledby` e `aria-checked` — falta roving tabindex e navegação por setas |
| Cancelar/navegação sem aviso de alterações não salvas | ✅ | `composables/useUnsavedChanges.js` (beforeunload + guard do Inertia), usado em `Questions/Create.vue:81`, `Edit.vue:283` e `Exams/Edit.vue:392` |
| `beforeunload` sem remoção no unmount em `Exams/Edit` | ✅ | `useUnsavedChanges.js:47-50` remove listener e guard no `onBeforeUnmount` |
| Regras de pontuação divergentes | ⚠️ | `numeric|min:0.5|max:10` unificado em `QuestionController@store/@update` e `DocumentController@importQuestions:145`; inputs com `min="0.5" step="0.5"` — resta o `min="1"` sem `step` do `points_override` no builder |
| Revisão da extração exige escolher matéria uma a uma | ✅ | Bloco "Aplicar a todas as selecionadas" (`Documents/Show.vue:104-127`) |
| Wizard de 4 passos | ✅ | 1 passo obrigatório + `<details>` "Configurações avançadas (opcional)" (`ExamConfigForm.vue:45-57`) |
| Estado do wizard só em memória (F5 apaga) | ✅ | Rascunho em `localStorage` por usuário (`Exams/Create.vue:88-141`), limpo no sucesso |
| "Criar Cópia" com delay artificial, copiando form não salvo sem confirmar | ✅ | `Questions/Edit.vue:377-419` — sem `setTimeout`, com `ConfirmDialog` quando `form.isDirty` |
| "Salvar e Continuar"/"Salvar e Sair" ambíguos | ❌ | Continua: "Salvar" (desabilitado sem mudanças) e "Concluir" (mesma ação + navegação) — `Exams/Edit.vue:170-190` |
| Sem `autocomplete`/`autofocus`/contador nos formulários internos | ✅ | Contador de caracteres (`Questions/Create.vue:52-54`), `autofocus` em `QuestionFormFields.vue:35` e `ExamConfigForm.vue:13` (com a ressalva do §4.3) |

### 3.6 Consistência, tipografia e cor (§3.5 antigo)

| Problema antigo | Status | Evidência atual |
|---|---|---|
| Dois design systems paralelos (classes `.*-elegant` vs Tailwind) | ⚠️ | `theme.css`/`components.css` removidos e **0 ocorrências** de classes `.*-elegant`. Mas persistem 2 sistemas de botão (`BaseButton` vs `PrimaryButton/SecondaryButton/DangerButton` do Breeze) e o CSS bespoke de `CopySuccessBanner` (§5) |
| Paleta duplicada em dois arquivos | ⚠️ | Só resta a do `tailwind.config.js:17-25` — porém é usada em **1** componente (`NavLink`), enquanto o app escreve `blue-600` cru em toda parte |
| Cores de dificuldade definidas 3 vezes | ⚠️ | `DifficultyBadge` criado e usado em `Questions/Index` e `QuestionList`; `ExamBuilder.vue:474-482` e `Questions/Show.vue:47-56` seguem com implementação própria e tons diferentes |
| Fonte divergente (Inter no Tailwind, Figtree no HTML) | ✅ | `app.blade.php:11` carrega **Inter** 400/500/600/700, casando com `tailwind.config.js:14` |
| Tema escuro em CSS sem acionador | ✅ | CSS removido; **0** ocorrências de `dark:` nos `.vue` |
| `breadcrumb-*` e `.skeleton` estilizados e nunca usados | ✅ | Viraram componentes reais: `Breadcrumbs` (via `PageHeader` nos 3 catálogos) e `Skeleton` (via `DataTable`) |
| Terminologia inconsistente | ⚠️ | "Exame" eliminado (0 ocorrências) e "Etiqueta" adotado nas telas. Ainda divergem: "disciplina", `tags: 'Tags'`, toasts "Tag criada/atualizada/excluída", "Média" vs "Médio" (§4.8) |
| Auth e Perfil em inglês | ✅ | 6 telas de auth + Perfil e partials em PT-BR |
| Login com design próprio; Register/Forgot no Breeze cru | ⚠️ | Todas passaram a usar `AuthLayout` (mesmo cromo), mas o conteúdo diverge: Login tem inputs com ícone, "olho" na senha e botão gradiente full-width; as outras 5 usam `TextInput`/`PrimaryButton` do Breeze com `!important` para forçar o azul |
| Ícones errados por fallback silencioso | ✅ | Mapa completo em `StatCard.vue:62-71`; `Exams/Index` não usa mais `StatCard` |
| Dois cards do Dashboard com a mesma cor | ✅ | 6 cores distintas (`Dashboard.vue:9-49`) |
| 20 `console.log` em produção | ✅ | Resta **1**, intencional e guardado por `import.meta.env.DEV` (`BaseButton.vue:36-39`) |
| Código morto que quebraria se acionado (`exportPDF` com `selectedExam` inexistente) | ✅ | Removido de `Exams/Create.vue` |
| Listeners globais nunca removidos | ✅ | `Exams/Show.vue:386-387`, `Documents/Index.vue:264-271`, `Questions/Index.vue:353-360` com cleanup |

### 3.7 Estados, feedback e ações destrutivas (§3.6 antigo)

| Problema antigo | Status | Evidência atual |
|---|---|---|
| Nenhum canal de notificação funcionando | ⚠️ | `flash` + `ToastHost` + `useToast()` funcionando (inclusive para erros fora do Inertia, `useFileDownload.js:33`) — com o defeito de empilhamento do §2.3 |
| 9 `confirm()` nativos em 4 padrões | ⚠️ | 8 telas migraram para `ConfirmDialog`; sobraram `confirm()` (`Exams/Show.vue:375`) e `alert()` (`Exams/Create.vue:254`) |
| Exclusão de prova sem avisar que remove vínculos | ⚠️ | `Exams/Index.vue:225-228` detalha a consequência; `Exams/Show`/`Exams/Edit` seguem com texto genérico |
| "Excluir" de questão que na verdade inativa | ✅ | Rótulo/ícone "Arquivar" + ação "Reativar" (`Questions/Index.vue:190-215`) e diálogo explicando a consequência |
| Redirect sem filtros após inativar | ✅ | `QuestionController@destroy:614-618` usa `back()`; a UI usa `bulk-status` com `preserveState/preserveScroll` |
| Modais sem Escape/foco preso/`role="dialog"` | ✅ | Todos envolvem `Modal.vue`, que usa `<dialog>` + `showModal()` (foco preso, Escape e scroll travado nativos) |
| Polling na listagem vs botão manual no detalhe | ✅ | `Documents/Show.vue:377-387` faz polling de 5s e para ao concluir (a listagem é que não para — §4.5) |
| Sem progresso/estimativa no processamento por IA | ⚠️ | Há cronômetro "Processando há Xs", mas contado a partir da abertura da tela, não do upload |
| Erros de exportação sem tratamento | ✅ | `composables/useFileDownload.js` via axios + `toast.error`; 0 ocorrências de `window.open`/`window.location` |
| Cards do Dashboard sem `role`/`tabindex` e com destino incoerente | ✅ | `QuestionList.vue:3-4` e `ExamList.vue:3-4` são `<Link>` para as telas de detalhe |
| Dificuldade em inglês cru no Dashboard | ✅ | `QuestionList.vue:24` usa `DifficultyBadge` |
| Modal de Matérias exibindo "0 questões" | ✅ | `SubjectController@index:12` com `withCount(['topics','questions'])` |
| 14 navegações com `<a :href>` | ⚠️ | Restam **2**, ambas em `Questions/Show.vue:13,17` |

### 3.8 Placar

- **Resolvidos:** 43 dos ~57 apontamentos verificáveis.
- **Parciais:** 12 — destaque para invalidação de cache (§4.11), modais/`AppModal`, terminologia, distribuição da prova ainda ignorada e o segundo sistema de botões.
- **Não resolvidos:** 4 — `confirm()`/`alert()` em Provas, `<a href>` em `Questions/Show`, ambiguidade "Salvar"/"Concluir", badge de dificuldade duplicado no builder.
- **Regressões novas introduzidas pela refatoração:** 6 — paginação da revisão de extração (§2.1), `onSubjectChange` inexistente (§2.2), sobreposição de toasts (§2.3), `AppModal` órfão (§2.5), `CopySuccessBanner` inalcançável (§2.6) e "Limpar Filtros" no-op em URL filtrada (§2.10).

---

## 4. Problemas por categoria (estado atual)

### 4.1 Navegação, telas redundantes e órfãs

| Problema | Local | Impacto | Correção sugerida | Prio |
|---|---|---|---|---|
| `AppModal` órfão (0 imports) enquanto 4 padrões de modal coexistem | `Components/UI/AppModal.vue` | Componente morto e a inconsistência que ele deveria eliminar permanece | Adotar em `ExamConfigEditModal`/`DeleteUserForm` ou remover | Alta |
| `CopySuccessBanner` inalcançável (props nunca enviadas) | `Questions/Edit.vue:207-213`; `QuestionController@edit` | 414 linhas + CSS próprio sem consumidor real; depois de duplicar, o usuário não sabe de qual questão veio | Enviar `is_copy`/`original_question_id` do controller ou trocar por toast com ação | Alta |
| Rotas sem consumidor: `questions.copy`, `questions.destroy` | `routes/web.php:39-41` | Superfície de API não exercitada divergindo do fluxo real | Remover ou ligar à UI | Baixa |
| `Exams/Show` e `Exams/Edit` duplicam as mesmas 4 ações com marcações diferentes | `Exams/Show.vue:47-140,370-390`; `Exams/Edit.vue:20-45,150-180` | Dois vocabulários visuais para Editar/Excluir/Preview/Exportar | Extrair barra de ações de prova compartilhada | Média |
| `ExamConfigForm` e `ExamConfigEditModal`: dois formulários para os mesmos dados, com validação e acessibilidade diferentes | `Components/Exams/ExamConfigForm.vue` vs `ExamConfigEditModal.vue` | Campos disponíveis na criação somem na edição (formatação, distribuições) | Unificar num componente de configuração | Média |
| Dashboard sem `<h1>`; Perfil é a única tela que usa o slot `#header` do layout (e ainda com `py-12` sobre o `py-8` do `main`) | `Dashboard.vue`; `Profile/Edit.vue:20-32` | Quebra de hierarquia semântica e de ritmo vertical | `PageHeader` nas duas | Média |
| Catálogos com capacidades desiguais: Matérias sem filtro, Tópicos com filtro por matéria, Etiquetas com busca textual | `Subjects/Index.vue` vs `Topics/Index.vue:63-72` vs `Tags/Index.vue:36-43` | Três comportamentos para três telas irmãs; Matérias fica inviável acima de ~40 registros | Padronizar busca + contador nas três | Média |
| Editar item de catálogo não move foco nem scroll (form no topo, lista embaixo) | `Subjects/Index.vue:145-150`, `Topics/Index.vue`, `Tags/Index.vue` | Em lista longa, clicar em "Editar" parece não fazer nada | `scrollIntoView` + foco no primeiro campo | Média |

### 4.2 Tabelas e listagens

**Crítico — paginação falsa em `Documents/Show`** (§2.1): `paginationLinks` fabrica `url: '#1'`/`'#prev'`/`'#next'` e entrega ao `Pagination`, que só sabe renderizar `<Link>` do Inertia. Não há `@click` nem interceptação de hash, e `currentPage` nunca muda. Correção: expor um evento de página no `Pagination` (ou usar botões locais) e atualizar `currentPage`.

| Problema | Local | Impacto | Correção | Prio |
|---|---|---|---|---|
| `isLoading` ligado ao `router.on('start')` **global** | `Questions/Index.vue:353-360`, `Exams/Index.vue:373-380`, `Documents/Index.vue:264-271` | Clicar em qualquer link para sair da tela faz a tabela piscar em skeleton | Filtrar pela URL da própria rota | Média |
| Colunas ordenáveis aquém do backend | `Questions/Index.vue:255-262` (sem `statement`/`created_at`) vs whitelist em `QuestionController.php:38` | Não há como ordenar por data de criação — a ordenação mais natural de um banco de questões | Marcar as colunas | Média |
| Filtros enviados e nunca renderizados | `Questions/Index.vue:266-274` (`topic_id`, `question_type_id`) + props `topics`/`question_types`/`tags` sem UI | Payload inútil em toda requisição e filtros prometidos que o usuário não alcança | Expor os campos ou remover | Média |
| Documentos sem ordenação por coluna (as outras duas têm) | `Documents/Index.vue:169-175` | Sem ordenar por data/status na tela mais temporal do produto | Adicionar `sortable` + whitelist no controller | Média |
| `per_page` no form sem controle na UI em Provas e Documentos (só Questões tem) | `Exams/Index.vue:213`, `Documents/Index.vue:190` | Mesma tabela, capacidades diferentes | Padronizar o seletor "Mostrar:" | Baixa |
| Busca de Provas sem `<label>` (só `placeholder`) enquanto as outras usam `FormField` | `Exams/Index.vue:47-53` | Campo sem nome acessível; placeholder some ao digitar | Usar `FormField` | Média |
| `DataTable`: `someSelected` é global, não relativo à página | `Components/UI/DataTable.vue:132` | Checkbox mestre indeterminado mesmo sem seleção na página atual | Comparar com as linhas visíveis | Baixa |
| `DataTable`: `<tbody>` do estado vazio sem `bg-white` (os outros dois têm) | `DataTable.vue:72-78` | Faixa cinza no card exatamente no estado vazio | Igualar classes | Baixa |
| Célula de enunciado com `cursor-pointer` sem ser clicável | `Questions/Index.vue:150` | Affordance falsa | Remover ou tornar a linha clicável | Baixa |
| Catálogos renderizam a lista inteira, sem paginação | `Subjects`, `Topics`, `Tags` | Degradação a partir de algumas centenas de itens | Paginar no servidor acima de N | Baixa |

### 4.3 Formulários e validação

| Problema | Local | Impacto | Correção | Prio |
|---|---|---|---|---|
| `onSubjectChange` inexistente (§2.2) | `Questions/Create.vue:33`, `Questions/Edit.vue:85` | Warning do Vue em dev; handler morto | Definir a função ou remover o listener | Alta |
| `QuestionFormFields` não usa `FormField` — repete rótulo/erro à mão em 6 campos | `Components/QuestionFormFields.vue:9-228` | O padrão de formulário do produto não vale no formulário mais usado do produto | Migrar para `FormField` | Média |
| `ExamConfigEditModal` com 8 `<label>` sem `for` e inputs sem `id` | `Components/Exams/ExamConfigEditModal.vue:23-90` | Clicar no rótulo não foca; leitor de tela não associa | `FormField` | Média |
| Textarea de cada alternativa sem rótulo acessível | `Components/AlternativesManager.vue:528-533` | Leitor de tela anuncia "campo de texto" sem contexto | `aria-label="Conteúdo da alternativa A"` | Média |
| `points_override` do builder com `min="1"` e sem `step` | `Components/Exams/ExamBuilder.vue:213-218` | Conflita com a regra unificada `min:0.5` — não dá para sobrescrever com 0,5 | `min="0.5" step="0.5"` | Média |
| Resumo de erros no topo sem foco nem `role="alert"`, duplicando os erros inline | `Questions/Create.vue:7-16`, `Questions/Edit.vue:8-17` | Em formulário longo, o usuário não vê o resumo | `tabindex="-1"` + foco + `role="alert"` | Média |
| `ExamConfigForm` não é `<form>` e valida sem mover foco ao campo inválido | `Components/Exams/ExamConfigForm.vue:1,478-492` | Enter não submete; o erro pode ficar fora da viewport com o bloco avançado aberto | `<form @submit.prevent>` + foco no 1º erro | Média |
| `autofocus` no `<select>` de matéria também na edição | `QuestionFormFields.vue:35` | A tela abre com scroll/foco no meio do formulário | Restringir à criação | Baixa |
| `Documents/Create` sem barra de progresso (`form.progress` disponível) e sem validação client-side de tipo/tamanho | `Pages/Documents/Create.vue:171-181` | Upload de 10 MB sem sinal além de "Enviando..."; erro de tipo só volta do servidor | Usar `form.progress` + checagem local | Média |
| `preserveScroll: true` no submit que redireciona | `Questions/Create.vue:257`, `Edit.vue:369` | Tela de destino abre na posição de scroll da anterior | Remover do submit | Baixa |
| `bulkTopicId` aplicado mesmo quando nulo, sobrescrevendo tópico já escolhido | `Documents/Show.vue:398-403` | "Aplicar a todas" apaga tópicos definidos individualmente | Só aplicar quando preenchido | Média |

### 4.4 Consistência de componentes e design system

| Problema | Local | Impacto | Correção | Prio |
|---|---|---|---|---|
| **Dois sistemas de botão**: `BaseButton` (11 arquivos) e o trio do Breeze (7 arquivos: 5 telas de auth + Perfil) | `Components/UI/BaseButton.vue` vs `Components/PrimaryButton.vue` e irmãos | Auth/Perfil parecem outro produto: cinza-escuro, `uppercase tracking-widest`, `text-xs` | Migrar auth/perfil para `BaseButton` e aposentar o trio | Alta |
| `PrimaryButton` é cinza-escuro por padrão e todos os consumidores corrigem com `!important` | `Register.vue:100`, `ForgotPassword.vue:57`, `ResetPassword.vue:88`, `ConfirmPassword.vue:44`, `VerifyEmail.vue:41` | O default do componente está errado; 5 telas carregam a correção | Corrigir o default | Média |
| `BaseButton` sem variante "danger outline" → 6 usos com `!important` | `Questions/Index.vue:196,204`; `Exams/Index.vue:139`; `Documents/Index.vue:120`; `Subjects/Index.vue:97`; `Topics/Index.vue` | API do componente incompleta; qualquer ajuste exige tocar em 6 arquivos | Adicionar `variant="danger-outline"` | Média |
| Terceiro estilo de botão nas Ações Rápidas do Dashboard (`uppercase tracking-widest`, verde/laranja) | `Dashboard.vue:57-84` | Três aparências de "botão primário" na mesma sessão | `BaseButton` com variantes | Média |
| `ConfirmDialog` não usa `BaseButton` e usa `rounded-lg` (o resto é `rounded-md`) | `Components/ConfirmDialog.vue:37-46` | Botão diferente exatamente no momento da decisão crítica | Migrar | Média |
| Header em gradiente azul→índigo só em `Exams/Show` e `Exams/Edit` | `Exams/Show.vue:6`, `Exams/Edit.vue:7` | 2 de 19 telas com linguagem visual própria | `PageHeader` (ou promover o gradiente a padrão de detalhe) | Média |
| `NavLink` (desktop) usa `primary-*`; `ResponsiveNavLink` (mobile) usa `indigo-*` | `Components/NavLink.vue:6`, `Components/ResponsiveNavLink.vue:15` | O item ativo troca de cor ao redimensionar a janela | Unificar no token `primary` | Média |
| Token `primary` do `tailwind.config.js` usado em 1 componente; o resto usa `blue-600` cru | `tailwind.config.js:17-25` | O design system está em dois lugares: o config (ignorado) e o hábito | Adotar `primary-*` ou remover o token | Média |
| `ExamBuilder` reimplementa o badge de dificuldade | `ExamBuilder.vue:474-482` | Duas fontes da verdade para as mesmas 3 cores | Usar `DifficultyBadge` | Média |
| `Questions/Show` reimplementa dificuldade e status inline com tons diferentes (`text-green-700` vs `text-green-800`) e "Inativa" em cinza onde a lista usa vermelho | `Questions/Show.vue:47-56,73-80` | O mesmo status muda de cor entre lista e detalhe | Usar `DifficultyBadge`/`StatusBadge` | Média |
| Tipografia de `h1` inconsistente: `text-3xl` (Questions/Exams Show) vs `text-2xl` (`PageHeader`, Documents) | várias telas | Hierarquia visual instável ao navegar | Padronizar via `PageHeader` | Média |
| Emojis misturados ao sistema de ícones SVG | `Exams/Create.vue:46`, `ExamPreview` (✏️/✓), `Documents/Create.vue:143`, `ExamBuilder.vue:119` | Renderização depende da fonte do SO; destoa do conjunto | Trocar por SVG | Baixa |
| `style="border-radius: 10px"` inline | `Exams/Show.vue:7` | Valor fora da escala (`rounded-xl` = 12px) | `rounded-xl` | Baixa |
| Comentários de topo dos componentes de UI descrevem o estado **anterior** no presente | `DataTable.vue:2-8`, `Pagination.vue:2-6`, `AppModal.vue:2-8`, `Breadcrumbs.vue:2-5`, `EmptyState.vue:2-6`, `Skeleton.vue:2-6`, `DifficultyBadge.vue:2-7` | Quem chegar novo vai caçar problemas já resolvidos | Reescrever como documentação de uso | Baixa |

### 4.5 Estados e feedback

| Problema | Local | Impacto | Correção | Prio |
|---|---|---|---|---|
| Toasts empilhados se sobrepõem (§2.3) | `ToastHost.vue:11` + `ToastNotification.vue:4` | Só a última mensagem fica legível | Remover `fixed/top/right` do `ToastNotification` (o host já posiciona) | Alta |
| `ToastNotification` tem **dois** botões de fechar (texto "Fechar" + "×") | `ToastNotification.vue:47-51,55-61` | Redundância confusa num componente pequeno | Manter só o "×" | Baixa |
| Overlay de download do preview é temporizado em 3s fixos, sem relação com o download real | `Components/Exams/ExamPreview.vue:496-516` | Some antes de terminar em arquivo grande e persiste mesmo quando o `useFileDownload` já mostrou erro | Aguardar a promise do download | Média |
| `Exams/Create`: `router.post` sem `onError` e sem desabilitar o botão durante o envio | `Exams/Create.vue:250-272` | Duplo clique cria duas provas; falha de validação não é sinalizada | `useForm` + `processing` | Média |
| Descartar rascunho no "Cancelar" sem confirmação | `Exams/Create.vue:243-246` | Perde config + questões escolhidas sem aviso, enquanto formulários menores são protegidos por `useUnsavedChanges` | `ConfirmDialog` | Média |
| "Salvar" e "Concluir" fazem a mesma coisa em `Exams/Edit` | `Exams/Edit.vue:170-190,317-333` | Ambiguidade em ação de escrita; "Salvar" desabilitado ao lado de "Concluir" habilitado confunde | Uma ação primária + "Salvar e sair" secundária | Média |
| `hasUnsavedChanges` ligado por `watch(examQuestions, deep)` | `Exams/Edit.vue:283-285` | Qualquer normalização inicial do array marca a prova como alterada e dispara o guard de saída *(inferido — validar)* | Comparar com snapshot inicial | Média |
| `Documents/Index`: o polling de 5s nunca para quando o processamento acaba, e não começa se o documento entra em `processing` depois | `Documents/Index.vue:246-262` | Requisição a cada 5s indefinidamente; banner some sem parar o timer | `watch(hasProcessingDocuments)` (o `Documents/Show.vue:377-387` já faz certo) | Média |
| `Documents/Show`: "Processando há Xs" conta a partir da abertura da tela | `Documents/Show.vue:299,371-374` | Documento rodando há 10 min exibe "3s" | Usar `document.created_at` | Baixa |
| Copy "✓ = Alternativa correta" numa UI que usa `radio` e não mostra "✓" | `Documents/Show.vue:283` | Instrução não corresponde à tela | Reescrever | Baixa |
| Feedback do Perfil é o texto "Salvo." do Breeze, fora do sistema de toasts | `Profile/Partials/*.vue`; `ProfileController@update` sem `->with('success')` | Duas gramáticas de confirmação no mesmo produto | Flash + toast | Média |
| `ConfirmDialog` usa `v-html` para a mensagem, que recebe conteúdo do usuário | `ConfirmDialog.vue:31` + `Questions/Index.vue:318-320` (trecho do enunciado interpolado) | Enunciado com HTML quebra ou injeta markup no diálogo | Escapar com interpolação e usar slot quando precisar de markup | Média |

### 4.6 Acessibilidade

| Problema | Local | Impacto | Prio |
|---|---|---|---|
| Dropzone de upload não é focável nem acionável por teclado (`<div @click>` + `<input class="hidden">`) | `Documents/Create.vue:24-38` | Impossível anexar arquivo só com teclado | Alta |
| Sem `aria-current="page"` no menu (desktop e mobile) | `NavLink.vue`, `ResponsiveNavLink.vue` | Leitor de tela não anuncia a seção atual — só a cor indica | Média |
| Sem *skip link* e sem foco no `main`/`h1` ao trocar de rota | `AppLayout.vue` | Usuário de teclado repassa o menu inteiro a cada navegação | Média |
| Menu de exportação de `Exams/Show` sem `aria-expanded`/`aria-haspopup`/`role="menu"`, sem Escape, fechado por `e.target.closest('.relative.group')` | `Exams/Show.vue:66-121,381-390` | Não anunciado, não fechável por teclado; seletor CSS frágil | Média |
| Gatilhos de `Dropdown` sem `aria-expanded`/`aria-haspopup` | `AppLayout.vue:53-63,80-90` | Idem, no menu principal | Média |
| `role="radiogroup"` de dificuldade sem roving tabindex nem setas | `QuestionFormFields.vue:128-174` | Os 3 botões entram na ordem de tabulação e as setas não funcionam | Média |
| Filtros do `ExamBuilder` (busca, matéria, dificuldade) sem rótulo | `ExamBuilder.vue:19-40` | Três campos anônimos na tela mais complexa do produto | Média |
| `<label>` usado para exibir dado somente-leitura (6 ocorrências) | `Questions/Show.vue:31,36,41,46,60,71` | Leitor de tela procura um campo associado inexistente | Baixa |
| `Questions/Show` navega com `<a href>` (recarrega tudo) | `Questions/Show.vue:13,17` | Perde o SPA; o contexto do leitor de tela reinicia | Média |
| Hierarquia de headings: Dashboard sem `h1`; Perfil começa em `h2`; `AuthLayout` começa em `h2` | `Dashboard.vue`, `Profile/Edit.vue:21`, `AuthLayout.vue:23` | 8 das 19 telas sem `h1` | Média |
| Animações locais não respeitam `prefers-reduced-motion` (o `app.css` respeita) | `Questions/Edit.vue:429-455`, `QuestionFormFields.vue:468-483` | Movimento persistente para quem pediu redução | Baixa |

### 4.7 Responsividade *(inferida do código — requer validação no browser)*

| Ponto de atenção | Local | Observação |
|---|---|---|
| 6 KPIs em `xl:grid-cols-6` | `Dashboard.vue:8` | Entre 1024–1280px caem para 3 colunas; abaixo de 768px viram 1 coluna com 6 cards empilhados |
| Página A4 (`width: 21cm`) dentro do modal de preview | `ExamPreview.vue` + `fitToContainer()` (`:455-470`) | O auto-zoom mitiga o overflow, mas só recalcula em mount/resize — não ao alternar orientação nos dados |
| `max-h-[70vh]` / `min-h-[50vh]` no builder | `ExamBuilder.vue:44,167` | Scroll aninhado em telas baixas (o problema antigo mudou de unidade, não de natureza) |
| Barra de ações em lote sem `flex-wrap` | `Questions/Index.vue:113-121` | Em 360px, texto + 2 botões podem comprimir demais |
| Até 5 botões-ícone na coluna "Ações" de Provas | `Exams/Index.vue:99-146` | Coluna larga dentro de `overflow-x-auto` — scroll horizontal provável em mobile |
| Ações de `Exams/Show` em `flex-col sm:flex-row` sobre header gradiente | `Exams/Show.vue:44-47` | 3 botões em coluna ocupam altura considerável em mobile |

### 4.8 Terminologia PT-BR

| Divergência | Onde |
|---|---|
| "disciplina" em vez de "matéria" | `DocumentController.php:151-152` (mensagens de validação) e `Documents/Show.vue:412` — enquanto o rótulo do próprio campo, dois blocos acima, é "Matéria" |
| "Tags" em vez de "Etiquetas" no resumo de erros | `resources/js/utils/fieldLabels.js:21` (`tags: 'Tags'`) |
| Toasts "Tag criada/atualizada/excluída com sucesso!" na tela chamada "Etiquetas" | `TagController.php:43,66,73` |
| Dificuldade média: **"Média"** (`QuestionController.php:135`, `Questions/Index.vue:344`) vs **"Médio"** (`DifficultyBadge.vue:31`, `QuestionFormFields.vue:156`, `ExamBuilder.vue:470`, `Documents/Show.vue`) | 4 arquivos contra 2 |
| "Preview" vs "Visualizar Prova" para a mesma ação | `Exams/Edit.vue:36` vs `Exams/Show.vue:50` e `Exams/Create.vue:41` |
| "Upload Documento" vs "Enviar e Processar" vs "Importar PDF" para o mesmo fluxo | `Documents/Index.vue:9`, `Documents/Create.vue:178`, `Dashboard.vue:76` |
| Tipos de questão em dois vocabulários: `multiple_choice`/`true_false`/`essay` na revisão de extração vs `multipla-escolha`/`verdadeiro-falso`/`dissertativa` no resto | `Documents/Show.vue:196-200` vs `QuestionController.php:...`; a ponte é um `typeMap` por **nome** em `DocumentController.php:164-178` | 

### 4.9 Código morto e resíduos

| Item | Local |
|---|---|
| `isMultipleAnswer`, `isVerdadeiroFalso`, `hasCorrectAnswer`, `addAlternative`, `removeAlternative`, `setCorrectAnswer` — 6 símbolos sem uso (o `AlternativesManager` faz tudo isso) | `Questions/Create.vue:187-236` **e** `Questions/Edit.vue:296-352` (duplicados nos dois) |
| `markAsChanged()` nunca chamado | `Exams/Edit.vue:295-297` |
| Bloco comentado de "tendência" + `ArrowTrendingUpIcon`/`ArrowTrendingDownIcon` importados sem uso + `CalendarIcon` no mapa sem uso + prop `change` sem consumidor | `Components/StatCard.vue:22-35,49-50,62-71` |
| Bloco "Dados para teste" condicionado a `$page.props.demo`, prop nunca compartilhada pelo middleware | `Auth/Login.vue:146-153` |
| `.slide-in` declarado e nunca aplicado | `Exams/Edit.vue:437-451` |
| `timeoutId` guardado e nunca limpo no unmount | `ToastNotification.vue:82,117-120` |
| `defineExpose({ validate })` sem nenhum pai consumindo | `QuestionFormFields.vue:449-459`, `AlternativesManager.vue:748-756` |
| Comentários "CORREÇÃO: Especificar qual tabela tem o user_id" e "// Especificar tabela" no código de produção | `DashboardController.php:71-72` |
| `selectedExam`/`examData` mantidos em `Exams/Index` só para o preview, com `examData` recalculando `total_points` que o backend já envia | `Exams/Index.vue:216-241` |

### 4.10 Backend e dados que impactam a tela

| Problema | Local | Impacto |
|---|---|---|
| Chips de etiqueta estilizados por `tag.color`, campo que **não existe** na tabela `tags` | `Exams/Show.vue:290-294` vs `create_tags_table` (sem `color`) e `ExamController@show` (`tags:id,name,slug`) | Gera `"undefined20"` — regra CSS inválida, chips sem cor. Bug visual silencioso |
| `Exams/Create` e `Exams/Edit` carregam **todas** as questões ativas com alternativas para o cliente | `ExamController@create:79-84`, `@edit:175-180` | Payload cresce linearmente com o banco de questões; o builder inteiro é client-side |
| `most_used_subjects` não filtra questões arquivadas | `DashboardController.php:71-90` | KPI conta questões inativas |
| `duplicate()` não redefine `user_id` no `replicate()` | `ExamController@duplicate:265-270` | Funciona hoje (só o dono chega lá), mas é frágil se a política mudar |
| `importQuestions` casa `QuestionType` por **nome**, com `typeMap` hardcoded e fallback silencioso para "Múltipla Escolha" | `DocumentController.php:164-178` | Renomear um tipo no seed quebra a importação sem aviso |
| Sem `Policy` para `Subject`/`Topic`/`Tag` — a proteção vem só do global scope dos models | `app/Policies/` (só `Exam` e `Question`) | Funciona (o scope gera 404), mas é implícita e fácil de perder num `withoutGlobalScopes()` |
| `QuestionController` mantém 4 caches de 30 min de dados auxiliares por usuário | `QuestionController.php:96-131,250-262` | Criar uma matéria pela tela de catálogo não invalida `questions_create_data_*`: o `<select>` do formulário de questão pode ficar até 30 min sem ela *(a criação inline contorna, mas o caminho pelo catálogo não)* |

### 4.11 Cache — o que sobrou da correção de backend

A correção do problema antigo ("questões sumindo por 30 min") foi feita **só na listagem**. As outras camadas de cache continuam com os mesmos defeitos estruturais:

| Problema | Local | Impacto no usuário | Prio |
|---|---|---|---|
| Criar/editar/excluir Matéria, Tópico ou Etiqueta **não invalida** `questions_create_data_*`, `questions_edit_data_*`, `subjects_all*`, `topics_all*`, `tags_all*` (TTL de 30 min) | `SubjectController`, `TopicController`, `TagController` (nenhum toca em `Cache`) vs `QuestionController.php:96-131,250-262` | O usuário cadastra uma matéria na tela de Cadastros e ela **não aparece** no `<select>` do formulário de questão nem no filtro da listagem por até 30 minutos. Exatamente o sintoma que a refatoração dizia ter eliminado | **Alta** |
| Importar questões de um documento não chama `clearQuestionCache()` | `DocumentController@importQuestions:180-210` (usa `Question::create` direto) | Depois de importar 30 questões, a lista mostra as novas (não é cacheada) mas os cards "Total/Ativas/Inativas/Dificuldade média" continuam nos números antigos por até 30 min | **Alta** |
| `Question::clearCache()` usa `Cache::tags()` e limpa chaves legadas que ninguém mais escreve (`questions_stats`, `questions_count_*`) | `app/Models/Question.php:52-63` | Com `CACHE_STORE=redis` (padrão atual) funciona; com `database`/`file` — alternativa **documentada no próprio `.env:47`** — `Cache::tags()` lança `BadMethodCallException` em **todo** `save()`, e criar questão passa a falhar com "Erro ao criar questão: …" | **Alta (risco)** |
| `CacheHelper` + `Question::hydratePaginator()` são código morto (só se referenciam entre si) | `app/Helpers/CacheHelper.php`, `app/Models/Question.php:66-80` | Resíduo do cache paginado removido | Baixa |
| `php artisan cache:questions` limpa apenas as chaves legadas | `app/Console/Commands/ClearQuestionsCache.php:22-28` | Comando de suporte que não resolve o problema real (as chaves por usuário) | Baixa |

---

## 5. Resquícios do design system antigo

**Confirmado removido:** `resources/css/theme.css` e `resources/css/components.css` não existem. O `resources/css/app.css` tem apenas `line-clamp`, 2 keyframes, 5 classes de delay e a regra `prefers-reduced-motion`. **Zero** ocorrências de `.btn-elegant`, `.card-elegant`, `.table-elegant`, `.empty-state-elegant`, `.skeleton`, `.page-title-elegant`, `.form-control-elegant` — as únicas menções à palavra "elegant" estão em **comentários** de 7 componentes de UI. Também sumiram o tema escuro morto (0 ocorrências de `dark:`), o `safelist` do `tailwind.config.js` e os 3 `ziggy.js` estáticos.

**O que ainda escapa do Tailwind:**

| Resíduo | Local | Por que importa |
|---|---|---|
| CSS bespoke completo (`.copy-success-banner`, `.type-success`, `.banner-content`, `.icon-container`, `.link`…) com gradientes e hexadecimais escritos à mão (`#f0fdf4`, `#bbf7d0`, `#dcfce7`…) | `Components/CopySuccessBanner.vue:180-414` | Mini design system paralelo — num componente que sequer é alcançável (§2.6) |
| `.line-clamp-2/3` redefinidos localmente, 3 vezes | `app.css:6-16`, `ExamBuilder.vue:490-495`, `Exams/Show.vue:388-398` | O Tailwind 3.4 já traz `line-clamp-*` nativo; hoje há 3 definições concorrentes |
| **Sobrescrita de uma utility do Tailwind** dentro de `<style scoped>` | `Exams/Show.vue:400-404` (`.transition-all { … }`) | Anti-padrão: muda o significado de uma classe do framework naquele componente |
| `style="border-radius: 10px"` inline | `Exams/Show.vue:7` | Valor fora da escala (`rounded-xl` = 12px) |
| `@media print { [class*="px-6 py-4"] { display: none } }` | `ExamPreview.vue` (bloco de print) | Seletor por substring de classe utilitária — quebra ao mudar qualquer padding |
| Seletor amplo `button svg { animation: … }` em `<style scoped>` | `QuestionFormFields.vue:468-483` | Anima **todo** SVG dentro de qualquer botão do componente |
| Trio de botões do Breeze (`PrimaryButton`/`SecondaryButton`/`DangerButton`) com `uppercase tracking-widest` | 7 telas (auth + Perfil) | Segundo sistema de botão, com defaults errados corrigidos por `!important` nos consumidores |
| Token `primary` definido e praticamente não usado | `tailwind.config.js:17-25` vs `blue-600` cru em 100+ lugares | O design system está em dois lugares: o config (ignorado) e o hábito |

### 5.1 Aderência ao design system recomendado para o segmento

O produto é uma **ferramenta de produtividade docente / SaaS educacional B2C-professor** (banco de questões → montagem de prova → exportação). O padrão de referência para esse segmento é: superfície neutra clara, uma cor de ação sóbria (azul/índigo/teal), cores semânticas reservadas para status, tipografia sans neutra com hierarquia forte (o usuário lê muito texto denso), densidade média em tabelas e nenhuma decoração competindo com o conteúdo.

**O que já está aderente (e não deve ser trocado):**

- **Paleta praticada:** azul (`blue-600`) para ação, verde/amarelo/vermelho estritamente semânticos (fácil/médio/difícil, ativo/inativo, sucesso/erro), cinza para superfícies. Coerente com o segmento e internamente consistente nos componentes novos (`BaseButton`, `StatusBadge`, `DifficultyBadge`).
- **Tipografia:** Inter (400/500/600/700) carregada em `app.blade.php:11` e declarada em `tailwind.config.js:14` — a divergência Inter/Figtree apontada antes está resolvida. Inter é adequada para tabelas e formulários densos.
- **Layout:** container `max-w-7xl`, cards `bg-white rounded-xl shadow-sm border border-gray-200`, espaçamento `space-y-6`. Padrão de dashboard/SaaS convencional e previsível.
- **Anti-padrões de indústria: ausentes.** Não há gradiente roxo/rosa "de IA" (o único gradiente é azul→índigo, e mesmo assim contido em 3 telas), não há dark pattern em ação destrutiva (o botão de confirmação é vermelho e o "Cancelar" tem o mesmo peso visual), não há cor de marca competindo com a semântica de erro, não há tema escuro pela metade.

**Divergências reais — e apenas as que resolvem problema concreto:**

| Divergência | Recomendação | Justificativa (problema que resolve) |
|---|---|---|
| A cor da marca é `blue-600` escrita à mão em 100+ lugares; o token `primary` existe e é ignorado | Passar `primary-600` a ser a fonte única (ou remover o token) | Não é questão estética: hoje **não existe** um lugar para mudar a cor de ação; e já produziu divergência real entre `NavLink` (primary) e `ResponsiveNavLink` (indigo), com o item ativo trocando de cor no breakpoint |
| Gradiente azul→índigo como cromo de 2 telas de Provas (e do `AuthLayout`) | Escolher: ou vira o padrão de "tela de detalhe", ou sai | Duas telas parecem de outro produto; a decisão atual é acidental, não intencional |
| Escala tipográfica de `h1` com 2 valores (`text-2xl` no `PageHeader`, `text-3xl` em 4 telas) | Fixar em `PageHeader` | O tamanho do título muda ao navegar entre telas do mesmo nível hierárquico |
| Sombras/raio: `rounded-xl` nos cards, `rounded-md` nos botões do `BaseButton`, `rounded-lg` no `ConfirmDialog` e nos inputs de várias telas | Definir 2 raios (superfície/controle) e aplicar | Hoje há 3 raios convivendo dentro do mesmo diálogo |
| Contraste do texto sobre chips coloridos por dado do usuário (`subject.color` + `'20'` de fundo com o mesmo hex no texto) | Calcular contraste ou fixar o texto em `gray-800` | Uma matéria em amarelo claro gera texto amarelo sobre fundo amarelo-claro — falha de leitura garantida, não hipotética *(inferido — validar no browser)* |

**Não recomendo trocar paleta nem tipografia.** O tema atual é adequado ao segmento, tem contraste correto nas cores de ação (`blue-600` sobre branco ≈ 5,1:1) e já é internamente consistente onde foi refatorado. O problema não é *qual* cor: é que ela não está tokenizada e que 3 telas fugiram do padrão.

---

## 6. Quick wins (baixo esforço, efeito imediato)

1. Remover `fixed top-4 right-4` de `ToastNotification.vue:4-5` — resolve a sobreposição de toasts (1 linha).
2. Corrigir a paginação em lotes de `Documents/Show` (evento de página em vez de `<Link href="#n">`) — destrava a revisão de documentos com mais de 10 questões.
3. Trocar o `confirm()` de `Exams/Show.vue:375` pelo `ConfirmDialog` já usado nas telas irmãs; idem `alert()` em `Exams/Create.vue:254`.
4. Corrigir o rótulo do card do Dashboard para "Questões" (ou filtrar `is_active` em `DashboardController.php:22`).
5. Corrigir a mensagem de exclusão de matéria (`Subjects/Index.vue:139-142`) para refletir o bloqueio real do backend.
6. Definir (ou remover) `onSubjectChange` em `Questions/Create.vue:33` e `Questions/Edit.vue:85`.
7. Chamar a invalidação de cache nos `store/update/destroy` de `SubjectController`/`TopicController`/`TagController` e em `DocumentController@importQuestions` — elimina os dois piores sintomas do §4.11.
8. Declarar `lodash-es` no `package.json`, subir `vue` para `^3.5`, remover a duplicata de `@inertiajs/vue3` e o `@tailwindcss/vite` v4.
9. `min="0.5" step="0.5"` no `points_override` do `ExamBuilder.vue:213-218`.
10. Terminologia: "disciplina" → "matéria" (`DocumentController.php:151-152`, `Documents/Show.vue:412`); `tags: 'Etiquetas'` (`fieldLabels.js:21`); toasts de `TagController` para "Etiqueta"; padronizar "Médio".
11. `aria-current="page"` em `NavLink`/`ResponsiveNavLink` e alinhar a cor ativa do mobile com `primary-*`.
12. Marcar `statement` e adicionar `created_at` como colunas ordenáveis em `Questions/Index.vue:255-262` (a whitelist do backend já aceita).
13. Trocar os 2 `<a :href>` de `Questions/Show.vue:13,17` por `<Link>` e usar `DifficultyBadge`/`StatusBadge` na mesma tela.
14. Adicionar `variant="danger-outline"` ao `BaseButton` e remover os 6 blocos de `!important`.
15. Escapar a mensagem do `ConfirmDialog` (trocar `v-html` por interpolação).
16. `PageHeader` no Dashboard e no Perfil — dá `<h1>` a 2 das 8 telas que não têm.
17. Tornar o dropzone de upload acionável por teclado (`role="button"`, `tabindex="0"`, `@keydown.enter/space`).
18. Apagar o código morto do §4.9 (6 símbolos duplicados em Create/Edit, `markAsChanged`, bloco `demo` do Login, `.slide-in`, `CacheHelper`/`hydratePaginator`).

---

## 7. O que só pode ser confirmado rodando a aplicação

- Comportamento real do `fitToContainer()` do preview entre 360px e 768px e ao alternar orientação nos dados da prova.
- Se o guard de alterações não salvas de `Exams/Edit` dispara falso-positivo já na abertura da tela (§4.5).
- Contraste real dos chips coloridos por dado do usuário (`subject.color` como fundo `+'20'` e como cor do texto).
- Scroll horizontal efetivo da coluna "Ações" de `Exams/Index` (5 botões) em telas estreitas.
- Warning do Vue para `onSubjectChange` (esperado apenas em build de desenvolvimento).
- Se `Cache::tags()` em `Question::clearCache()` quebra o cadastro de questões quando `CACHE_STORE` não é Redis (basta rodar com `CACHE_STORE=database`).
- Se a sobreposição de toasts ocorre também com um único toast por vez (ex.: import parcial que emite `success` e `error` no mesmo redirect).

---

## 8. Arquivos-chave para consulta

- `resources/js/Pages/Documents/Show.vue` — paginação em lotes quebrada (§2.1) e "disciplina" (§4.8)
- `resources/js/Components/ToastNotification.vue` + `Components/UI/ToastHost.vue` — sobreposição de toasts (§2.3)
- `resources/js/Pages/Questions/Create.vue` / `Edit.vue` — `onSubjectChange` inexistente e código morto duplicado
- `resources/js/Components/UI/AppModal.vue` — componente órfão (§2.5)
- `resources/js/Components/CopySuccessBanner.vue` — UI inalcançável + CSS bespoke (§2.6, §5)
- `resources/js/Pages/Exams/Show.vue` — `confirm()` nativo, dropdown inacessível, sobrescrita de utility, `style` inline
- `resources/js/Pages/Exams/Edit.vue` — cromo próprio, 5 estilos de botão, "Salvar" vs "Concluir"
- `resources/js/Components/Exams/ExamPreview.vue` — `question_type_id === 1`, overlay de download temporizado
- `resources/js/Components/UI/BaseButton.vue` — falta a variante `danger-outline` (6 `!important` no app)
- `app/Http/Controllers/QuestionController.php` + `app/Models/Question.php` + `SubjectController`/`TopicController`/`TagController` — invalidação de cache incompleta (§4.11)
- `app/Http/Controllers/DashboardController.php` — KPI "Questões Ativas" contando arquivadas
- `package.json` — dependências divergentes do código (§2.12)
