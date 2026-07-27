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
 * `router.on('before')` roda em toda visita Inertia, inclusive na do próprio
 * submit do botão "Salvar" — por isso só intercepta visitas GET (navegação
 * pra fora da tela). Um form.put/post/patch/delete nunca é bloqueado.
 * (Não dá pra distinguir isso checando `form.processing`: essa flag só vira
 * `true` dentro do `onStart` do useForm, que roda DEPOIS deste 'before'
 * global — então essa checagem nunca pega o próprio submit a tempo.)
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
            if (event.detail.visit.method !== 'get') {
                return;
            }
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
