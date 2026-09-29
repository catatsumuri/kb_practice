import {
    show,
    showByPath,
} from '@/actions/App/Http/Controllers/DocumentController';
import type { DocumentVisibility } from '@/types';

export const visibilityLabels: Record<DocumentVisibility, string> = {
    private: '非公開',
    public: '公開',
};

/**
 * The canonical URL for a document: its namespace slug and path when it has
 * both, otherwise its numeric id.
 */
export function documentHref(document: {
    id: number;
    path: string | null;
    namespace?: { slug: string } | null;
}) {
    return document.namespace && document.path
        ? showByPath({
              namespace: document.namespace.slug,
              path: document.path,
          })
        : show(document.id);
}
