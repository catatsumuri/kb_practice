import { Link } from '@inertiajs/react';
import {
    show,
    showByPath,
} from '@/actions/App/Http/Controllers/DocumentController';
import { show as showNamespace } from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { cn } from '@/lib/utils';
import type { DocumentNamespace, DocumentNavGroup } from '@/types';

type NamespaceNavigationProps = {
    namespace: Pick<DocumentNamespace, 'slug' | 'name'>;
    groups: DocumentNavGroup[];
    currentDocumentId: number;
};

/**
 * The left sidebar of a document page: the namespace as a header, then each
 * navigation group as a labelled section whose pages hang off a vertical
 * rail, with the current page marked on that rail.
 */
export function NamespaceNavigation({
    namespace,
    groups,
    currentDocumentId,
}: NamespaceNavigationProps) {
    return (
        <nav className="rounded-md border p-3 text-sm">
            <Link
                href={showNamespace(namespace.slug)}
                className="block border-b px-2 pb-3 text-base font-semibold text-foreground hover:underline"
            >
                {namespace.name}
            </Link>

            <div className="mt-4 space-y-6">
                {groups.map((group, index) => (
                    <section key={group.title ?? `group-${index}`}>
                        {group.title && (
                            <h3 className="mb-2 flex items-center gap-2 px-2 text-xs font-semibold tracking-wider text-foreground">
                                <span
                                    className="size-1.5 rounded-full bg-primary"
                                    aria-hidden="true"
                                />
                                {group.title}
                            </h3>
                        )}

                        <ul className="ml-[11px] border-l">
                            {group.documents.map((item) => {
                                const isCurrent = item.id === currentDocumentId;

                                return (
                                    <li key={item.id}>
                                        <Link
                                            href={
                                                item.path
                                                    ? showByPath({
                                                          namespace:
                                                              namespace.slug,
                                                          path: item.path,
                                                      })
                                                    : show(item.id)
                                            }
                                            prefetch
                                            aria-current={
                                                isCurrent ? 'page' : undefined
                                            }
                                            className={cn(
                                                '-ml-px block border-l-2 border-transparent py-1.5 pr-2 pl-3 leading-snug text-muted-foreground transition-colors hover:border-foreground/30 hover:bg-accent/40 hover:text-foreground',
                                                isCurrent &&
                                                    'border-primary bg-accent/60 font-medium text-foreground hover:border-primary',
                                            )}
                                        >
                                            {item.title}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </section>
                ))}
            </div>
        </nav>
    );
}
