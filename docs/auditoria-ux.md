# Auditoria de UX/UI — TestMaker

**Data:** 2026-07-25
**Stack:** Laravel 12 + Inertia 2 + Vue 3 + Tailwind 3 · 19 páginas Inertia, 30 componentes, 2 layouts, 3 arquivos CSS.
**Método:** leitura estática de 100% das rotas, páginas, componentes, layouts, CSS e controllers.

**Limitação declarada:** a aplicação não foi executada. Avaliações de responsividade derivam **exclusivamente de evidências no código** (breakpoints ausentes, dimensões fixas em cm/px, grids sem prefixo `sm:/md:/lg:`). Comportamentos que dependem de renderização real (quebra de texto, altura de linha, colisão de elementos, toque em mobile) precisam de validação no navegador/DevTools.

---

## 1. Árvore de navegação e telas mapeadas

```
/ ──> redirect /dashboard                          (routes/web.php:12)
│
├── PÚBLICO (guest)
│   ├── /login                 Auth/Login.vue          (layout próprio, PT-BR)
│   ├── /register              Auth/Register.vue       (GuestLayout, EN)
│   ├── /forgot-password       Auth/ForgotPassword.vue (GuestLayout, EN)
│   ├── /reset-password/{t}    Auth/ResetPassword.vue  (GuestLayout, EN)
│   ├── /confirm-password      Auth/ConfirmPassword.vue(GuestLayout, EN)
│   ├── /verify-email          Auth/VerifyEmail.vue    (GuestLayout, EN)
│   └── (órfã) Welcome.vue  ← sem rota; "/" redireciona
│
└── AUTENTICADO (AppLayout — SEM MENU DE NAVEGAÇÃO)
    ├── /dashboard             Dashboard.vue        hub único de navegação
    │     ├─ modal Matérias    Modals/SubjectsModal.vue
    │     ├─ modal Tópicos     Modals/TopicsModal.vue
    │     └─ modal Etiquetas   Modals/TagsModal.vue
    ├── /questions             Questions/Index.vue  tabela + filtros + paginação
    │   ├── /questions/create  Questions/Create.vue
    │   ├── /questions/{id}    Questions/Show.vue
    │   └── /questions/{id}/edit Questions/Edit.vue
    ├── /exams                 Exams/Index.vue      tabela SEM paginação
    │   ├── /exams/create      Exams/Create.vue     wizard 4 passos + builder
    │   ├── /exams/{id}        Exams/Show.vue
    │   └── /exams/{id}/edit   Exams/Edit.vue       builder drag&drop
    ├── /documents             Documents/Index.vue  tabela + polling 5s
    │   ├── /documents/create  Documents/Create.vue upload drag&drop
    │   └── /documents/{id}    Documents/Show.vue   revisão da extração IA
    └── /profile               Profile/Edit.vue     ⚠ usa OUTRO layout (AuthenticatedLayout)
```

| # | Tela | Arquivo | Função | Layout | Tabela | Paginação | Filtros | Vazio | Loading |
|---|------|---------|--------|--------|--------|-----------|---------|-------|---------|
| 1 | Dashboard | `Pages/Dashboard.vue` | Hub + KPIs | AppLayout | — | — | — | parcial | ✗ |
| 2 | Questões | `Pages/Questions/Index.vue` | Banco de questões | AppLayout | `.table-elegant` | servidor | 4 campos + per_page | ✓ | ✗ |
| 3 | Nova Questão | `Pages/Questions/Create.vue` | Formulário | AppLayout | — | — | — | — | ✓ botão |
| 4 | Editar Questão | `Pages/Questions/Edit.vue` | Formulário + cópia | AppLayout | — | — | — | — | ✓ botão |
| 5 | Ver Questão | `Pages/Questions/Show.vue` | Leitura | AppLayout | — | — | — | — | ✗ |
| 6 | Provas | `Pages/Exams/Index.vue` | Lista de provas | AppLayout | Tailwind cru | **nenhuma** | busca client-side | ✓ | ✗ |
| 7 | Nova Prova | `Pages/Exams/Create.vue` | Wizard + builder | AppLayout | — | — | — | ✓ builder | ✗ |
| 8 | Editar Prova | `Pages/Exams/Edit.vue` | Builder | AppLayout | — | — | — | ✓ builder | parcial |
| 9 | Ver Prova | `Pages/Exams/Show.vue` | Detalhe + export | AppLayout | lista | — | — | ✓ | ✗ |
| 10 | Documentos | `Pages/Documents/Index.vue` | Lista de uploads | AppLayout | Tailwind cru | servidor | **nenhum** | ✓ | ✓ spinner |
| 11 | Upload | `Pages/Documents/Create.vue` | Drag & drop | AppLayout | — | — | — | — | ✓ botão |
| 12 | Revisar extração | `Pages/Documents/Show.vue` | Edição em massa | AppLayout | cards | **nenhuma** | — | ✓ | ✓ estado |
| 13 | Perfil | `Pages/Profile/Edit.vue` | Conta | **AuthenticatedLayout** | — | — | — | — | ✓ |
| 14–19 | Auth (6 telas) | `Pages/Auth/*` | Autenticação | Guest/próprio | — | — | — | — | ✓ |

**Telas órfãs/duplicadas:** `Welcome.vue` (sem rota), `Layouts/AuthenticatedLayout.vue` (usado só pelo Perfil), componentes nunca importados: `AuthCard.vue`, `Checkbox.vue`, `DashboardCard.vue`, `ToastNotification.vue`.

**Funcionalidades de backend sem porta de entrada na UI:** `exams.toggle-publish`, `exams.duplicate`, `questions.copy` (rota dedicada) — `routes/web.php:37-39,34-35`, zero referências em `resources/js`.

---

## 2. Resumo executivo — 10 problemas mais críticos

| # | Problema | Evidência | Impacto | Prio |
|---|----------|-----------|---------|------|
| 1 | **Não existe menu de navegação.** O `AppLayout` (usado por 12 das 13 telas internas) tem apenas logo + "Sair". Para ir de Questões → Provas o usuário precisa voltar ao Dashboard. | `Layouts/AppLayout.vue:17-46` | Navegação em estrela obrigatória; +2 cliques em toda tarefa cruzada | **Alta** |
| 2 | **Todas as mensagens de sucesso/erro são silenciosamente descartadas.** O middleware Inertia não compartilha `flash`; nenhum controller consegue falar com o usuário. | `Http/Middleware/HandleInertiaRequests.php:30-38` vs 20+ `->with('success', …)` nos controllers; `Documents/Index.vue:5` lê `$page.props.flash?.success` (sempre `undefined`) | Salvar, excluir, importar 30 questões: **nenhum feedback**. Usuário não sabe se funcionou | **Alta** |
| 3 | **Tela de Perfil provavelmente quebra.** Usa `AuthenticatedLayout`, que referencia `route('subjects.index')` — rota inexistente (só `store/update/destroy` estão registradas). Ziggy lança erro ao resolver. | `Layouts/AuthenticatedLayout.vue:32,127` vs `routes/web.php:28` | Tela inacessível/erro em tela branca | **Alta** (validar no browser) |
| 4 | **Listagem de Provas sem paginação**, carregando todas as provas **com todas as questões** de uma vez. | `ExamController@index:24-34`; `Exams/Index.vue:71` renderiza `filteredExams` inteiro | Degradação progressiva; a partir de ~50 provas a tela trava | **Alta** |
| 5 | **Preview de prova a partir da listagem está quebrado**: `await router.get(...)` do Inertia não retorna resposta — `response.props` estoura e cai no `catch`, exibindo preview vazio; além disso a rota faz um *full visit* que remonta a página. | `Exams/Index.vue:362-370` + `ExamController@questions:119-135` | Ação principal da tela ("Visualizar") falha silenciosamente | **Alta** |
| 6 | **Montagem de prova é exclusivamente drag & drop, em grid fixo de 12 colunas sem breakpoints.** | `Components/Exams/ExamBuilder.vue:2,4,120` (`grid-cols-12` / `col-span-5` / `col-span-7`) | Inutilizável em tablet/celular e por teclado; sem alternativa "Adicionar" | **Alta** |
| 7 | **Três padrões diferentes de tabela e quatro de confirmação destrutiva** convivendo. | Tabelas: `Questions/Index.vue:177` (`.table-elegant`) · `Exams/Index.vue:72` · `Documents/Index.vue:59` (Tailwind cru). Confirmação: modal ad-hoc (`Questions/Index.vue:428`), `ConfirmDialog` (`Exams/Edit.vue:165`), `confirm()` nativo (9 ocorrências), `alert()` (`Exams/Create.vue:225`) | Sistema parece 3 produtos distintos; comportamento imprevisível | **Alta** |
| 8 | **Cards de estatística do Dashboard mentem sobre interatividade.** `StatCard` não declara a prop `link` nem trata clique. Os cards "Questões"/"Provas" (com `:link`) **não navegam**; os de Matérias/Tópicos/Etiquetas abrem modal só por *fallthrough* de evento, sem `cursor-pointer`, foco ou `role`. | `Components/StatCard.vue:44-54` vs `Dashboard.vue:12,19,26,33,41` | 2 de 5 cards são cliques mortos; 3 são clicáveis invisíveis e inacessíveis por teclado | **Alta** |
| 9 | **Configuração da prova é "teatro"**: o wizard de 4 passos coleta distribuição por dificuldade, por tópico e nº-alvo de questões, e nada disso é usado na montagem. | `ExamConfigForm.vue:127-238` grava os dados; `ExamBuilder.vue:264-289` só consome `targetPoints` | Esforço do usuário sem retorno; 4 passos antes de poder trabalhar | **Alta** |
| 10 | **Título do navegador é sempre "TestMaker"** — o callback ignora o título da página; só 8 de 19 telas sequer definem `<Head>`. | `resources/js/app.js:12-13` | Múltiplas abas indistinguíveis; histórico e favoritos inúteis | Média |

---

## 3. Problemas por categoria

### 3.1 Navegação, telas redundantes e órfãs

| Problema | Local | Impacto | Correção sugerida | Prio |
|---|---|---|---|---|
| Dois layouts autenticados com cromo totalmente diferente (um com menu, outro sem) | `Layouts/AppLayout.vue` vs `Layouts/AuthenticatedLayout.vue`; `Profile/Edit.vue:21` | Ir ao Perfil "muda de sistema" | Unificar num único layout com navegação (barra ou sidebar) e aposentar o outro | Alta |
| Matérias/Tópicos/Etiquetas só existem como modais do Dashboard | comentário em `routes/web.php:25-30`; `Dashboard.vue:109-111` | Ao criar questão sem matérias cadastradas, o `select` fica vazio e não há caminho para criar uma sem abandonar o formulário (`QuestionFormFields.vue:11-22`) | Criar telas/rotas próprias ou permitir "criar nova matéria" inline no formulário | Alta |
| `Welcome.vue` inalcançável (a `/` redireciona) | `routes/web.php:12-14` | Código morto que diverge do Login real | Remover ou transformar em landing | Baixa |
| 4 componentes nunca importados | `AuthCard`, `Checkbox`, `DashboardCard`, `ToastNotification` | Manutenção enganosa; `ToastNotification` seria justamente a solução do problema #2 | Remover ou adotar | Média |
| 3 arquivos Ziggy estáticos e desatualizados (contêm `exams.preview`, `documents.process` — inexistentes; faltam `exams.duplicate`, `questions.copy`) | `resources/js/ziggy.js`, `resources/js/Components/ziggy.js`, `public/js/ziggy.js` | Risco de alguém importar a versão errada | Apagar; `@routes` já injeta o Ziggy correto (`app.blade.php:16`) | Média |
| Dashboard calcula `most_used_subjects` e `total_documents` e não exibe nenhum dos dois | `DashboardController.php:26-28,71-90` vs `Dashboard.vue` | Documentos (área principal) não tem KPI nem card; dado gerado e jogado fora | Exibir ou remover | Média |

### 3.2 Padronização de tabelas

| Problema | Local | Impacto | Correção | Prio |
|---|---|---|---|---|
| Sem paginação em Provas e na revisão de documentos | `Exams/Index.vue:71-165`; `Documents/Show.vue:114-256` | Listas longas viram scroll infinito e travamento | Paginar no servidor (padrão de Questões) | Alta |
| Busca client-side em Provas vs server-side em Questões vs nenhuma em Documentos | `Exams/Index.vue:258-277`; `QuestionController@applyFilters:144-172`; `Documents/Index.vue` sem filtro | Usuário aprende um comportamento e ele muda de tela para tela; em Documentos não há como achar um arquivo entre 200 | Padronizar filtro server-side com debounce nas três | Alta |
| Nenhuma tabela permite **ordenar por coluna** nem **seleção múltipla/ações em lote** | as três | Inativar 20 questões = 20 modais | Adicionar sort nos cabeçalhos e checkbox de seleção | Média |
| Paginação com `<a href>` (recarrega a página inteira, perde o SPA) e três aparências diferentes | `Questions/Index.vue:369-384`; `Documents/Index.vue:142-150` | Piscada branca a cada página; perde scroll | Trocar por `<Link>` e extrair um `<Pagination>` compartilhado | Média |
| Estado "vazio" com 4 tratamentos visuais distintos | `.empty-state-elegant` (`Questions/Index.vue:390`), `Documents/Index.vue:47`, `Exams/Index.vue:169`, `ExamBuilder.vue:221` | Inconsistência perceptível | Componente `<EmptyState>` único | Média |
| Mensagem de vazio quase sempre errada em Questões: `hasFilters` considera `per_page`, que **sempre** vem preenchido | `Questions/Index.vue:500-502` | Usuário novo, sem questões, lê "não encontramos questões com os filtros aplicados" e vê botão "Limpar Filtros" | Excluir `per_page` (e valores vazios) do cálculo | Média |
| Nenhuma tabela tem estado de **carregando** (nem skeleton nem spinner) durante filtro/paginação | as três; a classe `.skeleton` existe em `resources/css/components.css:288-306` e tem **0 usos** | Ao filtrar, a tela fica congelada sem sinal de progresso | Usar `form.processing`/`router.on('start')` + skeleton já estilizado | Média |
| Ações da linha são só ícones, sem rótulo acessível (`title` não substitui `aria-label`) | `Questions/Index.vue:332-353`; `Exams/Index.vue:131-160`; `Documents/Index.vue:115-128` — e **0 ocorrências de `aria-` em todo o `resources/js`** | Leitor de tela anuncia "botão" sem contexto | Adicionar `aria-label` | Média |
| Ações em Documentos são links de texto azul/laranja/vermelho, enquanto nas outras são botões-ícone | `Documents/Index.vue:115-128` | Mesma tarefa, affordance diferente | Padronizar | Baixa |
| Cache de 30 min sobre o resultado paginado das questões; a invalidação por padrão depende de Redis e é *no-op* silencioso em qualquer outro driver | `QuestionController.php:63-86` e `613-632` (`if (config('cache.default') === 'redis')`); `.env:47` sugere `database` como alternativa | Se o driver mudar, o usuário cria uma questão e ela **não aparece por 30 minutos** | Invalidar por tags/`Cache::flush` do escopo, ou não cachear a listagem | Alta (risco) |

### 3.3 Responsividade *(inferida do código — requer validação no browser)*

| Problema | Local | Impacto | Correção | Prio |
|---|---|---|---|---|
| Builder de prova em `grid-cols-12` + `col-span-5/7` **sem nenhum breakpoint** | `ExamBuilder.vue:2,4,120` | Em telas <768px as duas colunas continuam lado a lado, com ~150px cada | `grid-cols-1 lg:grid-cols-12` + colunas empilhadas | Alta |
| Preview da prova com largura fixa de página A4 (`width: 21cm` ≈ 794px) dentro de modal, e **zero classes responsivas em 601 linhas** | `Components/Exams/ExamPreview.vue:564-570`, sem nenhum `sm:/md:/lg:` no arquivo | Overflow horizontal garantido em mobile | Escala com `transform: scale()` ou wrapper com scroll + zoom | Alta |
| `grid grid-cols-4` fixo no cabeçalho de edição de prova | `Exams/Edit.vue:41` (e `grid-cols-4` no `safelist` do `tailwind.config.js:12-14`, indício de correção anterior "na marra") | 4 KPIs espremidos em telas pequenas | `grid-cols-2 md:grid-cols-4` | Média |
| Regra global `@media (max-width:640px){ .btn-elegant{ width:100% } }` | `resources/css/theme.css:860-864` | Os botões-ícone da coluna "Ações" da tabela de questões (`btn-elegant p-1.5`) viram **blocos de largura total**, quebrando a linha | Restringir a `.btn-elegant-block` ou excluir botões-ícone | Alta |
| `@media (max-width:640px){ .table-elegant{ display:block } }` altera o modelo de layout da tabela | `resources/css/theme.css:866-869` | Colunas podem perder alinhamento em vez de rolar | Aplicar `overflow-x` no wrapper, não na `<table>` | Média |
| `max-h-[600px]` / `min-h-[600px]` fixos no builder | `ExamBuilder.vue:45,152` | Em telas baixas gera scroll aninhado (scroll dentro de scroll) | Altura relativa a `vh` | Média |
| Alinhamento de botões de filtro feito com `style="margin-top: 2rem"` / `2.3rem` | `Questions/Index.vue:105,110` | Desalinha assim que o rótulo quebra em 2 linhas | `flex items-end` sem margem mágica | Média |
| 45 ocorrências de `style="…"` inline (majoritariamente `animation-delay`) | todo `resources/js` | Animações escalonadas em cascata atrasam a leitura da lista até ~1s e ignoram `prefers-reduced-motion` | Mover para classes; respeitar `prefers-reduced-motion` | Média |

### 3.4 Formulários

| Problema | Local | Impacto | Correção | Prio |
|---|---|---|---|---|
| Resumo de erros exibe **nomes técnicos de campo** | `Questions/Create.vue:8-10` e `Questions/Edit.vue:9-11` (`<strong>{{ field }}</strong>`) | Usuário lê "alternatives.0.content: …", "subject_id: …" | Mapa campo→rótulo em PT-BR ou remover o resumo, deixando só os erros inline | Alta |
| `<label>` sem `for`/`id` em praticamente todos os formulários internos | `QuestionFormFields.vue:8,30,56,80,133`; `Questions/Create.vue:40,57,80`; `ExamConfigForm.vue:44,56,65,80…` (o Login, `Auth/Login.vue:47,74,121`, faz certo) | Clique no rótulo não foca o campo; leitor de tela não associa | Adicionar `for`/`id` | Alta |
| Seletor de dificuldade feito com 3 `<button>` sem `aria-pressed`/`role="radiogroup"` | `QuestionFormFields.vue:84-122` | Não navegável como grupo de opções; estado não anunciado | `radiogroup` ou inputs `radio` estilizados | Média |
| "Cancelar" e navegação **não avisam sobre alterações não salvas** nos formulários de questão | `Questions/Create.vue:113-116`, `Questions/Edit.vue:165-168` (`<a href>` = recarga total) | Perda silenciosa de um enunciado longo | `<Link>` + guarda `router.on('before')` | Alta |
| Aviso de saída na edição de prova registra `beforeunload` **sem remover no unmount** | `Exams/Edit.vue:338-343` | Listener sobrevive à navegação SPA: o usuário passa a receber "deseja sair?" em telas onde não há mudança nenhuma | `onMounted`/`onBeforeUnmount` + guarda do Inertia | Alta |
| Regras divergentes de pontuação entre importação e edição | `DocumentController.php:132` (`numeric|min:0`) vs `QuestionController.php:305,467` (`integer|min:1|max:10`) | Questão importada com 0,5 ou 12 pontos **não pode ser salva** ao ser aberta para edição, sem explicação clara | Unificar a regra (e o `step` do input, `Documents/Show.vue:179`) | Alta |
| Revisão da extração exige escolher disciplina questão a questão | `Documents/Show.vue:184-197` | Importar 30 questões = 30 selects idênticos | "Aplicar disciplina/tópico a todas as selecionadas" | Alta |
| Wizard de 4 passos só valida o passo 1; passos 2–4 são opcionais e sem efeito prático | `ExamConfigForm.vue:723-742` | Caminho longo até o valor; usuário preenche metas que o sistema ignora | Reduzir a 1 passo obrigatório + "configurações avançadas" opcionais; ou implementar de fato a seleção automática por distribuição | Alta |
| Estado do wizard só existe em memória | `Exams/Create.vue:102-148` | F5 apaga tudo | Persistir rascunho (localStorage ou registro `draft`) | Média |
| Botão "Criar Cópia" tem `setTimeout(600ms)` artificial e copia o formulário **não salvo**, saindo da tela sem confirmar | `Questions/Edit.vue:396-414` | Lentidão fabricada + risco de perder a edição em andamento | Remover o delay; confirmar antes de sair | Média |
| "Salvar e Continuar"/"Salvar e Sair" duplicam a mesma função com rótulos parecidos, e o primeiro fica desabilitado sem explicação | `Exams/Edit.vue:120-136` | Ambiguidade | Um botão "Salvar" + "Concluir" | Baixa |
| Sem `autocomplete`, sem `autofocus` e sem contador/limite nos formulários internos (padrão que o Login/Register já seguem) | `Questions/Create.vue`, `ExamConfigForm.vue` | Digitação repetitiva | Padronizar | Baixa |

### 3.5 Consistência de componentes, tipografia e cor

| Problema | Local | Impacto | Correção | Prio |
|---|---|---|---|---|
| **Dois design systems paralelos**: classes semânticas `.btn-elegant/.card-elegant/.table-elegant/.form-control-elegant` (`theme.css`) e utilitários Tailwind escritos à mão | `theme.css:311-504` vs `Dashboard.vue:51`, `Exams/Index.vue:12`, `Questions/Create.vue:119` | Mesmo botão primário aparece em **≥5 variações** (azul arredondado maiúsculo, verde, gradiente, `btn-elegant-primary`, `px-6 py-2.5`) | Escolher um sistema e migrar; começar pelos botões | Alta |
| Paleta duplicada em dois lugares, com os mesmos hexadecimais | `theme.css:10-19` (`--color-primary-*`) vs `tailwind.config.js:20-28` (`colors.primary`) | Divergem no próximo ajuste de marca | Fonte única (tokens CSS → Tailwind) | Média |
| Cores de dificuldade definidas 3 vezes, com aparências diferentes | gradientes em `components.css:241-257`; `bg-green-100/yellow-100/red-100` em `Questions/Show.vue:46-48`; outra variação em `ExamBuilder.vue:420-427` | A mesma "questão fácil" tem 3 aparências no produto | Componente `<DifficultyBadge>` | Média |
| Fonte declarada duas vezes e divergente: `Inter` no CSS/Tailwind, **Figtree** carregada no HTML | `theme.css:112`, `tailwind.config.js:17-19` vs `resources/views/app.blade.php:10-11` | Inter nunca é baixada → fallback do sistema; tipografia diferente da projetada | Carregar a fonte certa | Média |
| Tema escuro completo implementado em CSS e **sem nenhum acionador** | `theme.css:127-145`, `components.css:79-82,115-117,172-174,197-199` + botão `.dark-mode-toggle:120-132`; **0 ocorrências de `dark`/`dark:` nos `.vue`** | ~80 linhas de CSS morto; expectativa não cumprida | Implementar o toggle ou remover | Baixa |
| `breadcrumb-*` e `.skeleton` estilizados e nunca usados | `components.css:84-105, 288-306` | Idem | Adotar breadcrumb (ajudaria muito o problema #1) | Média |
| Terminologia inconsistente: "Prova" × "Exame", "Média" × "Médio", "Matéria" × "Disciplina", "Tag" × "Etiqueta" | `Exams/Show.vue:11,55,288` ("Exame") vs `Exams/Index.vue:7` ("Provas"); `Questions/Index.vue:301` "Média" vs `QuestionFormFields.vue:107` "Médio"; `Documents/Show.vue:185` "Disciplina" vs restante "Matéria"; `Dashboard.vue:39` "Etiquetas" vs formulários "Tags" | Usuário duvida se são coisas diferentes | Glossário e revisão de microcopy | Média |
| Telas de autenticação e Perfil **em inglês** no meio de um produto PT-BR | `Auth/Register.vue:29,45,60,76,100,108`; `Profile/Edit.vue:26`; partials de Perfil | Primeira e última impressão do produto quebradas | Traduzir | Alta |
| Login tem design próprio (gradiente, ícones, "olho" na senha); Register/Forgot usam o Breeze cru | `Auth/Login.vue` vs `Auth/Register.vue` | Fluxo de cadastro parece de outro produto | Aplicar o mesmo layout | Média |
| Ícones errados por *fallback* silencioso: `FolderIcon` e `CalendarIcon` não existem no mapa do `StatCard` | `Dashboard.vue:31` e `Exams/Index.vue:32` vs mapa em `StatCard.vue:56-65` (cai em `QuestionMarkCircleIcon`) | Card "Tópicos" e "Próxima Prova" exibem ícone de interrogação | Completar o mapa | Média |
| Dois cards do Dashboard usam a mesma cor (`orange`) | `Dashboard.vue:33,40` | Perda de codificação visual | Cores distintas | Baixa |
| 20 `console.log`/`console.error` em produção, incluindo dump de objetos | ex.: `Questions/Index.vue:525`, `Exams/Edit.vue:262`, `Questions/Edit.vue:396,403` | Ruído e vazamento de dados no console | Remover | Baixa |
| Código morto que quebraria se acionado: `exportPDF/exportDOCX` referenciam `selectedExam` inexistente | `Exams/Create.vue:249-271` | `ReferenceError` latente | Remover | Baixa |
| Listeners globais nunca removidos | `Exams/Show.vue:378-382` (`document.addEventListener('click')`) | Acumulam a cada visita SPA | `onUnmounted` | Média |

### 3.6 Estados, feedback e ações destrutivas

| Problema | Local | Impacto | Correção | Prio |
|---|---|---|---|---|
| Nenhum canal de notificação funcionando (ver #2) — o componente existe mas não é usado | `HandleInertiaRequests.php:30-38`; `Components/ToastNotification.vue` (órfão); estilos `.toast-*` em `theme.css:506-562` | Impacto transversal: nenhuma ação confirma sucesso | Compartilhar `flash` no middleware + montar `<ToastNotification>` no layout | **Alta** |
| Confirmações destrutivas em 4 padrões, sendo 9 delas `confirm()` nativo | `SubjectsModal.vue:217`, `TagsModal.vue:229`, `TopicsModal.vue:248`, `Documents/Index.vue:242,248`, `Exams/Index.vue:373`, `Exams/Show.vue:372` vs `ConfirmDialog.vue` (usado 1 vez) | Caixa do navegador ignora o design, não permite detalhar consequências e é bloqueante | Adotar `ConfirmDialog` em todas | Alta |
| Exclusão de prova não avisa que **remove os vínculos** e é definitiva no banco | `ExamController@destroy:197-207` (`detach()` + `delete()`) | Perda irreversível com aviso genérico | Detalhar consequências / soft delete + desfazer | Média |
| "Excluir" de questão na verdade **inativa** (o ícone é uma lixeira) | `QuestionController@destroy:560-574`; ícone lixeira em `Questions/Index.vue:350-352` | Modelo mental errado: o usuário acha que apagou | Ícone/rótulo "Arquivar/Inativar" + ação de reativar | Média |
| Após inativar, redireciona para `questions.index` **sem os filtros/página** | `QuestionController.php:572` | Usuário na página 4 filtrada volta para a página 1 sem filtros | `back()` ou preservar query string | Alta |
| Modais customizados sem `Escape`, sem foco preso, sem `role="dialog"`, sem travar o scroll do fundo | `SubjectsModal/TagsModal/TopicsModal`, `ExamPreview`, `ExamConfigEditModal`, `ConfirmDialog`, modal ad-hoc em `Questions/Index.vue:426-463` — só `Modal.vue:48-61` e `Dropdown.vue:19-26` tratam `Escape` | Usuário fica "preso" no modal; teclado inutilizável | Reusar `Modal.vue` como base de todos | Alta |
| Progresso de processamento com dois comportamentos: polling automático de 5s na listagem, **botão manual** no detalhe | `Documents/Index.vue:178-184` vs `Documents/Show.vue:26-28` | Na tela onde o usuário espera o resultado, ele precisa recarregar na mão | Padronizar polling nas duas | Média |
| Sem barra de progresso/estimativa no processamento por IA (que "leva alguns minutos") | `Documents/Show.vue:18-29` | Ansiedade e abandono | Etapas/percentual ou tempo estimado | Média |
| Erros de exportação PDF/DOCX não têm tratamento na UI (`window.location`/`window.open`) | `Exams/Index.vue:385-407`, `Exams/Show.vue:359-369`, `Exams/Edit.vue:327-335` | Falha no servidor = aba em branco | Feedback de download + tratamento de erro | Média |
| Cliques em cards da lista do Dashboard sem `role`/`tabindex` e com destino incoerente (questão recente abre **edição**, não visualização) | `Components/QuestionList.vue:3-5`; `ExamList.vue:3-5` | Inacessível por teclado; ação primária diferente da lista de questões | `<Link>` + destino `show` | Média |
| Dificuldade exibida em inglês cru no Dashboard | `Components/QuestionList.vue:25` (`{{ question.difficulty_level }}` → "easy"/"medium"/"hard") | Texto não traduzido na tela inicial | Usar o mesmo badge das outras telas | Média |
| Modal de Matérias exibe "0 questões" sempre — o controller não carrega `questions_count` | `SubjectsModal.vue:118` vs `DashboardController.php:91` (`withCount('topics')` apenas) | Informação falsa | `withCount(['topics','questions'])` | Média |
| 14 navegações internas usam `<a :href>` em vez de `<Link>` (recarga completa) | `Questions/Index|Create|Edit|Show`, `Documents/Index|Create|Show` | Perde o SPA: tela branca, scroll perdido, estado perdido | Trocar por `<Link>` | Média |

---

## 4. Quick wins (alto impacto, baixo esforço)

1. **Compartilhar `flash` no middleware** e montar `<ToastNotification>` no `AppLayout` — ~15 linhas destravam ~20 mensagens de sucesso/erro já escritas nos controllers. (`HandleInertiaRequests.php:30-38`)
2. **Adicionar navegação ao `AppLayout`** (as `NavLink`/`ResponsiveNavLink` já existem e estão prontas em `AuthenticatedLayout.vue:19-35`) — remover o link para `subjects.index`.
3. **Corrigir/aposentar `AuthenticatedLayout`** removendo `route('subjects.index')` — desbloqueia a tela de Perfil.
4. **Corrigir `title:` em `app.js:12-13`** para `${title} - ${appName}` e adicionar `<Head>` nas 11 páginas sem título.
5. **Declarar a prop `link` no `StatCard`** (ou envolvê-lo em `<Link>`) + `cursor-pointer` + `role="button"`/`tabindex` — cards do Dashboard passam a funcionar como aparentam.
6. **Completar o mapa de ícones do `StatCard`** com `FolderIcon` e `CalendarIcon`.
7. **Traduzir Register/Forgot/Reset/VerifyEmail/Perfil** para PT-BR.
8. **Trocar os 9 `confirm()`/2 `alert()` pelo `ConfirmDialog`** já existente.
9. **`hasFilters` sem `per_page`** (`Questions/Index.vue:500-502`) — corrige o estado vazio enganoso.
10. **`back()` em vez de `redirect()->route('questions.index')`** no `destroy` (`QuestionController.php:572`) — preserva filtros e página.
11. **Remover 20 `console.log`, o `setTimeout(600)` da cópia e o código morto de `Exams/Create.vue:249-271`.**
12. **Rótulos técnicos → rótulos humanos** no resumo de erros (`Questions/Create.vue:8-10`, `Edit.vue:9-11`).
13. **Restringir `.btn-elegant{width:100%}` em <640px** (`theme.css:860-864`) para não explodir a coluna de ações da tabela.
14. **Apagar os 3 arquivos `ziggy.js` obsoletos** e os 4 componentes órfãos.

---

## 5. Arquivos-chave para consulta

- `resources/js/Layouts/AppLayout.vue` — layout sem navegação (problema #1)
- `app/Http/Middleware/HandleInertiaRequests.php` — `flash` ausente (problema #2)
- `resources/js/Layouts/AuthenticatedLayout.vue` — rota inexistente (problema #3)
- `app/Http/Controllers/ExamController.php` — index sem paginação; `questions()` incompatível com o cliente
- `resources/js/Pages/Exams/Index.vue` — preview quebrado, tabela sem paginação
- `resources/js/Components/Exams/ExamBuilder.vue` — grid fixo, drag-only
- `resources/js/Components/Exams/ExamPreview.vue` — página A4 fixa, sem breakpoints
- `resources/js/Components/StatCard.vue` — prop `link` não declarada, mapa de ícones incompleto
- `resources/js/Pages/Questions/Index.vue` — referência do padrão de tabela a ser generalizado
- `resources/css/theme.css` / `components.css` — design system paralelo, dark mode e skeleton mortos
