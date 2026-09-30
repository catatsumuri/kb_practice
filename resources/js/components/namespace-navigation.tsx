import { Link, useRemember } from '@inertiajs/react';
import type { Page } from '@inertiajs/core';
import { ChevronDown } from 'lucide-react';
import { useEffect } from 'react';
import {
    show,
    showByPath,
} from '@/actions/App/Http/Controllers/DocumentController';
import { show as showNamespace } from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { Badge } from '@/components/ui/badge';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import type {
    DocumentNamespace,
    DocumentNavItem,
    DocumentNavNode,
} from '@/types';

type NamespaceNavigationProps = {
    namespace: Pick<DocumentNamespace, 'slug' | 'name'>;
    nodes: DocumentNavNode[];
    currentDocumentId: number;
    onDocumentNavigate?: (documentId: number) => void;
};

type NavigationNodeProps = {
    namespace: Pick<DocumentNamespace, 'slug'>;
    node: DocumentNavNode;
    currentDocumentId: number;
    depth: number;
    nodePath: string;
    onDocumentNavigate?: (documentId: number) => void;
};

function preservesNavigation(page: Page, namespaceSlug: string): boolean {
    const document = page.props.document as
        | { namespace?: { slug: string } | null }
        | undefined;

    return (
        window.matchMedia('(min-width: 1024px)').matches &&
        page.component === 'documents/show' &&
        document?.namespace?.slug === namespaceSlug
    );
}

function documentNavigationOptions(
    namespaceSlug: string,
    onDocumentNavigate?: (documentId: number) => void,
) {
    return {
        preserveState: (page: Page) => preservesNavigation(page, namespaceSlug),
        preserveScroll: (page: Page) =>
            preservesNavigation(page, namespaceSlug),
        onSuccess: (page: Page) => {
            const document = page.props.document as { id: number } | undefined;

            if (document && preservesNavigation(page, namespaceSlug)) {
                onDocumentNavigate?.(document.id);
            }
        },
    };
}

function containsDocument(node: DocumentNavNode, documentId: number): boolean {
    return (
        node.document?.id === documentId ||
        node.children.some((child) => containsDocument(child, documentId))
    );
}

function documentUrl(
    namespace: Pick<DocumentNamespace, 'slug'>,
    document: DocumentNavItem,
) {
    return document.path
        ? showByPath({ namespace: namespace.slug, path: document.path })
        : show(document.id);
}

function NavigationLabel({ label }: { label: string | null }) {
    if (!label) {
        return null;
    }

    return (
        <Badge
            variant="outline"
            className="px-1.5 py-0 text-[10px] font-normal text-muted-foreground"
        >
            {label}
        </Badge>
    );
}

function NavigationLeaf({
    namespace,
    node,
    currentDocumentId,
    onDocumentNavigate,
}: Omit<NavigationNodeProps, 'depth'>) {
    if (!node.document) {
        return null;
    }

    const isCurrent = node.document.id === currentDocumentId;

    return (
        <Link
            href={documentUrl(namespace, node.document)}
            prefetch
            {...documentNavigationOptions(namespace.slug, onDocumentNavigate)}
            aria-current={isCurrent ? 'page' : undefined}
            className={cn(
                '-ml-px flex items-start gap-2 border-l-2 border-transparent py-1.5 pr-2 pl-3 leading-snug text-muted-foreground transition-colors hover:border-foreground/30 hover:bg-accent/40 hover:text-foreground',
                isCurrent &&
                    'border-primary bg-accent/60 font-medium text-foreground hover:border-primary',
            )}
        >
            <span className="min-w-0 flex-1">{node.title}</span>
            <NavigationLabel label={node.label} />
        </Link>
    );
}

function NavigationBranch({
    namespace,
    node,
    currentDocumentId,
    depth,
    nodePath,
    onDocumentNavigate,
}: NavigationNodeProps) {
    const containsCurrent = containsDocument(node, currentDocumentId);
    const [open, setOpen] = useRemember(
        depth === 0 || containsCurrent,
        `namespaceNavigation:${namespace.slug}:${nodePath}`,
    );

    useEffect(() => {
        if (containsCurrent) {
            setOpen(true);
        }
    }, [containsCurrent]);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            {node.title && (
                <div
                    className={cn(
                        'flex items-center gap-1.5',
                        depth === 0 ? 'mb-2 px-2' : 'py-1 pr-1 pl-3',
                    )}
                >
                    {depth === 0 && (
                        <span
                            className="size-1.5 shrink-0 rounded-full bg-primary"
                            aria-hidden="true"
                        />
                    )}

                    {node.document ? (
                        <Link
                            href={documentUrl(namespace, node.document)}
                            prefetch
                            {...documentNavigationOptions(
                                namespace.slug,
                                onDocumentNavigate,
                            )}
                            aria-current={
                                node.document.id === currentDocumentId
                                    ? 'page'
                                    : undefined
                            }
                            className={cn(
                                'min-w-0 flex-1 font-semibold text-foreground hover:underline',
                                depth === 0
                                    ? 'text-xs tracking-wider'
                                    : 'text-sm',
                            )}
                        >
                            {node.title}
                        </Link>
                    ) : (
                        <CollapsibleTrigger asChild>
                            <button
                                type="button"
                                className={cn(
                                    'min-w-0 flex-1 text-left font-semibold text-foreground hover:underline',
                                    depth === 0
                                        ? 'text-xs tracking-wider'
                                        : 'text-sm',
                                )}
                            >
                                {node.title}
                            </button>
                        </CollapsibleTrigger>
                    )}

                    <NavigationLabel label={node.label} />

                    <CollapsibleTrigger
                        className="rounded-sm p-0.5 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        aria-label={open ? '折りたたむ' : '展開する'}
                    >
                        <ChevronDown
                            className={cn(
                                'size-3.5 transition-transform',
                                !open && '-rotate-90',
                            )}
                        />
                    </CollapsibleTrigger>
                </div>
            )}

            <CollapsibleContent>
                <ul
                    className={cn(
                        'border-l',
                        depth === 0 ? 'ml-[11px]' : 'ml-3',
                    )}
                >
                    {node.children.map((child, index) => (
                        <li
                            key={
                                child.document?.id ??
                                `${child.title ?? 'node'}-${index}`
                            }
                        >
                            <NavigationNode
                                namespace={namespace}
                                node={child}
                                currentDocumentId={currentDocumentId}
                                depth={depth + 1}
                                nodePath={`${nodePath}.${index}`}
                                onDocumentNavigate={onDocumentNavigate}
                            />
                        </li>
                    ))}
                </ul>
            </CollapsibleContent>
        </Collapsible>
    );
}

function NavigationNode(props: NavigationNodeProps) {
    if (props.node.children.length === 0) {
        return <NavigationLeaf {...props} />;
    }

    return <NavigationBranch {...props} />;
}

/**
 * The left sidebar of a document page. Navigation nodes may link to a page,
 * contain labelled child nodes, or do both. Top-level groups remain expanded;
 * nested branches open automatically when they contain the current document.
 */
export function NamespaceNavigation({
    namespace,
    nodes,
    currentDocumentId,
    onDocumentNavigate,
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
                {nodes.map((node, index) => (
                    <NavigationNode
                        key={
                            node.document?.id ??
                            `${node.title ?? 'node'}-${index}`
                        }
                        namespace={namespace}
                        node={node}
                        currentDocumentId={currentDocumentId}
                        depth={0}
                        nodePath={String(index)}
                        onDocumentNavigate={onDocumentNavigate}
                    />
                ))}
            </div>
        </nav>
    );
}
