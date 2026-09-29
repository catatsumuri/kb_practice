import { Form, Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import { lang } from '@erag/lang-sync-inertia/react';
import { extractMarkdownHeadings } from '@catatsumuri/inkstream';
import type { ResolveWikilink } from '@catatsumuri/inkstream';
import { InkstreamMarkdown } from '@catatsumuri/inkstream/react';
import {
    PanelLeftClose,
    PanelLeftOpen,
    PanelRightClose,
    PanelRightOpen,
} from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import {
    create,
    destroy,
    edit,
    index,
    show,
    showByPath,
} from '@/actions/App/Http/Controllers/DocumentController';
import { show as showNamespace } from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { fetch as fetchOgp } from '@/actions/App/Http/Controllers/OgpController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import { DocumentMeta } from '@/components/document-meta';
import { usePersistedBoolean } from '@/hooks/use-persisted-boolean';
import { useSyncedScroll } from '@/hooks/use-synced-scroll';
import { visibilityLabels } from '@/lib/document';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

import type {
    DocumentNavItem,
    DocumentPermissions,
    DocumentWithUser,
} from '@/types';

type ShowDocumentProps = {
    document: DocumentWithUser;
    namespaceDocuments: DocumentNavItem[];
    can: DocumentPermissions;
};

export default function ShowDocument({
    document,
    namespaceDocuments,
    can,
}: ShowDocumentProps) {
    const { __ } = lang();
    const { auth } = usePage().props;
    const namespace = document.namespace;
    const [showSource, setShowSource] = useState(false);
    const hasSource = Boolean(document.source_content);
    const hasNamespaceNav = Boolean(namespace) && namespaceDocuments.length > 0;
    const [showNav, setShowNav] = usePersistedBoolean(
        'documents.showNav',
        true,
    );
    const [showRightPanel, setShowRightPanel] = usePersistedBoolean(
        'documents.showRightPanel',
        true,
    );
    const navBeforeSourceRef = useRef(showNav);
    const translationPaneRef = useRef<HTMLDivElement>(null);
    const sourcePaneRef = useRef<HTMLDivElement>(null);
    const splitViewVisible = Boolean(document.source_content) && showSource;

    useSyncedScroll(translationPaneRef, sourcePaneRef, {
        enabled: splitViewVisible,
    });

    // Comparing translation and original side by side already needs both
    // columns' worth of room, so showing the source view hides the left
    // nav to free up space, and hiding it again restores whatever state
    // the nav was in beforehand.
    function toggleSource() {
        if (showSource) {
            setShowSource(false);
            setShowNav(navBeforeSourceRef.current);
        } else {
            navBeforeSourceRef.current = showNav;
            setShowSource(true);
            setShowNav(false);
        }
    }

    const headings = useMemo(
        () => extractMarkdownHeadings(document.content),
        [document.content],
    );

    // Resolves [[Title]] wikilinks against this document's namespace
    // siblings (already permission-filtered server-side via
    // namespaceDocuments). An unresolved title links to that namespace's
    // create form instead — a "red link" the reader can follow to write
    // the missing page, the same convention as e.g. Wikipedia.
    const resolveWikilink: ResolveWikilink | undefined = useMemo(() => {
        if (!namespace) {
            return undefined;
        }

        const byTitle = new Map(
            namespaceDocuments.map((item) => [item.title, item]),
        );

        return (path) => {
            const target = byTitle.get(path);

            if (!target) {
                return { url: create(namespace.slug).url, exists: false };
            }

            return {
                url: target.path
                    ? showByPath({
                          namespace: namespace.slug,
                          path: target.path,
                      }).url
                    : show(target.id).url,
                exists: true,
            };
        };
    }, [namespace, namespaceDocuments]);

    const ogpEndpoint = fetchOgp().url;

    // Owners get the full "ドキュメント > namespace > title" trail. Everyone
    // else (other logged-in users and guests, i.e. "public mode") only ever
    // reaches a document through its namespace or the public dashboard, so
    // the breadcrumb root is the namespace itself when there is one —
    // regardless of whether that namespace is open or closed. A namespace-
    // less document falls back to the dashboard crumb for logged-in users,
    // or drops the parent crumb entirely for guests, who can't reach it.
    const titleCrumb = { title: document.title, href: show(document.id) };

    setLayoutProps({
        wide: true,
        breadcrumbs: can.update
            ? [
                  { title: 'ドキュメント', href: index() },
                  ...(document.namespace
                      ? [
                            {
                                title: document.namespace.name,
                                href: showNamespace(document.namespace.slug),
                            },
                        ]
                      : []),
                  titleCrumb,
              ]
            : document.namespace
              ? [
                    {
                        title: document.namespace.name,
                        href: showNamespace(document.namespace.slug),
                    },
                    titleCrumb,
                ]
              : auth.user
                ? [{ title: 'ダッシュボード', href: dashboard() }, titleCrumb]
                : [titleCrumb],
    });

    return (
        <>
            <Head title={document.title} />

            <main className="grid min-w-0 gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-2">
                        {hasNamespaceNav && (
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                onClick={() => setShowNav((open) => !open)}
                                aria-label={
                                    showNav
                                        ? __('Hide page list')
                                        : __('Show page list')
                                }
                            >
                                {showNav ? (
                                    <PanelLeftClose className="size-4" />
                                ) : (
                                    <PanelLeftOpen className="size-4" />
                                )}
                            </Button>
                        )}
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {(can.update || can.delete) && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={edit(document.id)}>編集</Link>
                                </Button>

                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                        >
                                            削除
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogTitle>
                                            このドキュメントを削除しますか？
                                        </DialogTitle>
                                        <DialogDescription>
                                            削除したドキュメントは元に戻せません。
                                        </DialogDescription>

                                        <Form
                                            {...destroy.form(document.id)}
                                            options={{
                                                preserveScroll: true,
                                            }}
                                            className="space-y-6"
                                        >
                                            {({ processing }) => (
                                                <DialogFooter className="gap-2">
                                                    <DialogClose asChild>
                                                        <Button
                                                            type="button"
                                                            variant="secondary"
                                                        >
                                                            キャンセル
                                                        </Button>
                                                    </DialogClose>

                                                    <Button
                                                        variant="destructive"
                                                        disabled={processing}
                                                        asChild
                                                    >
                                                        <button type="submit">
                                                            削除
                                                        </button>
                                                    </Button>
                                                </DialogFooter>
                                            )}
                                        </Form>
                                    </DialogContent>
                                </Dialog>
                            </>
                        )}

                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={() => setShowRightPanel((open) => !open)}
                            aria-label={
                                showRightPanel
                                    ? __('Hide sidebar')
                                    : __('Show sidebar')
                            }
                        >
                            {showRightPanel ? (
                                <PanelRightClose className="size-4" />
                            ) : (
                                <PanelRightOpen className="size-4" />
                            )}
                        </Button>
                    </div>
                </div>

                <Card className="min-w-0">
                    <CardContent>
                        <div className="lg:flex lg:items-start lg:gap-6">
                            {hasNamespaceNav && showNav && namespace && (
                                <aside
                                    className="mb-4 self-start overflow-y-auto lg:sticky lg:top-4 lg:mb-0 lg:w-64 lg:flex-shrink-0"
                                    style={{
                                        maxHeight: 'calc(100vh - 2rem)',
                                    }}
                                >
                                    <nav className="rounded-md border p-4 text-sm">
                                        <Link
                                            href={showNamespace(namespace.slug)}
                                            className="mb-2 block font-semibold text-foreground hover:underline"
                                        >
                                            {namespace.name}
                                        </Link>
                                        <ul className="space-y-1">
                                            {namespaceDocuments.map((item) => (
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
                                                        className={cn(
                                                            'block rounded px-1 py-0.5 text-muted-foreground hover:text-foreground',
                                                            item.id ===
                                                                document.id &&
                                                                'font-medium text-foreground',
                                                        )}
                                                    >
                                                        {item.title}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    </nav>
                                </aside>
                            )}

                            <div className="min-w-0 lg:flex-1">
                                {document.source_content && showSource ? (
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="rounded-md border">
                                            <p className="border-b bg-muted/40 px-4 py-2 text-xs font-medium text-muted-foreground">
                                                {__('Translation')}
                                            </p>
                                            <div
                                                ref={translationPaneRef}
                                                className="overflow-y-auto p-4"
                                                style={{
                                                    maxHeight:
                                                        'calc(100vh - 2rem)',
                                                }}
                                            >
                                                <InkstreamMarkdown
                                                    ogpEndpoint={ogpEndpoint}
                                                    resolveWikilink={
                                                        resolveWikilink
                                                    }
                                                >
                                                    {document.content}
                                                </InkstreamMarkdown>
                                            </div>
                                        </div>
                                        <div className="rounded-md border">
                                            <p className="border-b bg-muted/40 px-4 py-2 text-xs font-medium text-muted-foreground">
                                                {__('Original')}
                                            </p>
                                            <div
                                                ref={sourcePaneRef}
                                                className="overflow-y-auto p-4"
                                                style={{
                                                    maxHeight:
                                                        'calc(100vh - 2rem)',
                                                }}
                                            >
                                                <InkstreamMarkdown
                                                    ogpEndpoint={ogpEndpoint}
                                                >
                                                    {
                                                        document.source_content
                                                    }
                                                </InkstreamMarkdown>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <InkstreamMarkdown
                                        ogpEndpoint={ogpEndpoint}
                                        resolveWikilink={resolveWikilink}
                                    >
                                        {document.content}
                                    </InkstreamMarkdown>
                                )}
                            </div>

                            {showRightPanel && (
                                <aside
                                    className="mt-4 self-start overflow-y-auto lg:sticky lg:top-4 lg:mt-0 lg:w-80 lg:flex-shrink-0"
                                    style={{
                                        maxHeight: 'calc(100vh - 2rem)',
                                    }}
                                >
                                    <div className="flex flex-col gap-4">
                                        <div className="rounded-md border p-4">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <CardTitle>
                                                    {document.title}
                                                </CardTitle>
                                                <Badge variant="secondary">
                                                    {
                                                        visibilityLabels[
                                                            document.visibility
                                                        ]
                                                    }
                                                </Badge>
                                            </div>
                                            <DocumentMeta
                                                author={document.user.name}
                                                createdAt={document.created_at}
                                            />
                                            {document.source_url && (
                                                <p className="mt-2 text-sm text-muted-foreground">
                                                    {__('Translated from:')}{' '}
                                                    <a
                                                        href={
                                                            document.source_url
                                                        }
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="underline"
                                                    >
                                                        {document.source_title ??
                                                            document.source_url}
                                                    </a>
                                                    {document.source_author && (
                                                        <>
                                                            {' '}
                                                            {__('(:author)', {
                                                                author: document.source_author,
                                                            })}
                                                        </>
                                                    )}
                                                </p>
                                            )}
                                            {hasSource && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="mt-1 -ml-2 h-auto w-fit gap-1 px-2 py-0.5"
                                                    onClick={toggleSource}
                                                >
                                                    {showSource ? (
                                                        <PanelRightClose className="size-4" />
                                                    ) : (
                                                        <PanelRightOpen className="size-4" />
                                                    )}
                                                    {showSource
                                                        ? __('Hide original')
                                                        : __('Show original')}
                                                </Button>
                                            )}
                                        </div>

                                        {headings.length > 0 && (
                                            <nav className="rounded-md border p-4 text-sm">
                                                <p className="mb-2 font-semibold text-foreground">
                                                    {__('Contents')}
                                                </p>
                                                <ul className="space-y-1">
                                                    {headings.map((heading) => (
                                                        <li
                                                            key={heading.id}
                                                            style={{
                                                                paddingLeft: `${(heading.level - 1) * 12}px`,
                                                            }}
                                                        >
                                                            <a
                                                                href={`#${encodeURIComponent(heading.id)}`}
                                                                onClick={(
                                                                    event,
                                                                ) => {
                                                                    event.preventDefault();
                                                                    window.document
                                                                        .getElementById(
                                                                            heading.id,
                                                                        )
                                                                        ?.scrollIntoView(
                                                                            {
                                                                                block: 'start',
                                                                            },
                                                                        );
                                                                }}
                                                                className="block rounded px-1 py-0.5 text-muted-foreground hover:text-foreground"
                                                            >
                                                                {heading.text}
                                                            </a>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </nav>
                                        )}
                                    </div>
                                </aside>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
