import { onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Avisa antes de sair de uma tela com alterações não salvas — tanto ao
 * fechar/recarregar a aba (beforeunload) quanto ao navegar dentro da SPA
 * via <Link>/router (Inertia não intercepta isso sozinho). Antes, formulários
 * como Questions/Create|Edit usavam <a href> para "Cancelar" só para forçar
 * um full reload; com <Link> de volta, esse guard evita perder um enunciado
 * longo por engano.
 *
 * IMPORTANTE: `hasChanges` também dispara na navegação do próprio submit
 * (router.on('before') roda em toda visita Inertia, inclusive form.post do
 * seu botão "Salvar"). Combine sempre com `!form.processing`, senão o
 * usuário recebe um "sair sem salvar?" ao tentar salvar:
 *   useUnsavedChanges(() => form.isDirty && !form.processing)
 *
 * @param {() => boolean} hasChanges getter reativo
 * @param {string} [message]
 */
export function useUnsavedChanges(hasChanges, message = 'Você tem alterações não salvas. Deseja sair sem salvar?') {
    const onBeforeUnload = (e) => {
        if (hasChanges()) {
            e.preventDefault();
            e.returnValue = '';
        }
    };

    let removeInertiaGuard;

    onMounted(() => {
        window.addEventListener('beforeunload', onBeforeUnload);
        removeInertiaGuard = router.on('before', (event) => {
            if (hasChanges() && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', onBeforeUnload);
        removeInertiaGuard?.();
    });
}
