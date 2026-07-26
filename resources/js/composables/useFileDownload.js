import { useToast } from '@/composables/useToast';

/**
 * Downloads de PDF/DOCX hoje usam window.location.href / window.open sem
 * nenhum tratamento: se o servidor falhar, o usuário só vê uma aba em
 * branco. Isso busca o arquivo via axios (com o mesmo XHR autenticado da
 * SPA), garante o nome do arquivo a partir do header e mostra um toast se
 * a geração falhar no servidor.
 */
export function useFileDownload() {
    const toast = useToast();

    const download = async (url, options = {}) => {
        const { errorMessage = 'Não foi possível gerar o arquivo. Tente novamente.' } = options;

        try {
            const response = await window.axios.get(url, { responseType: 'blob' });

            const disposition = response.headers['content-disposition'] || '';
            const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
            const filename = match ? decodeURIComponent(match[1]) : 'download';

            const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(blobUrl);
        } catch {
            toast.error(errorMessage);
        }
    };

    return { download };
}
