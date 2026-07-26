<!--
    Casco do cabeçalho em gradiente das telas de detalhe (Exams/Show,
    Exams/Edit, Questions/Show). Antes cada tela declarava o próprio
    `bg-gradient-to-r from-blue-600 to-indigo-700` com um raio/padding
    ligeiramente diferente (inclusive um `style="border-radius: 10px"`
    solto) — aqui os três concordam com um só valor. O conteúdo interno
    (badges, métricas, ações) continua livre via slot, porque varia demais
    entre prova/questão para caber num slot fixo.
-->
<template>
    <div class="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
        <div class="p-6 sm:p-8">
            <slot />
        </div>
    </div>
</template>
