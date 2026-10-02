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
import { useEffect, useMemo, useRef, useState } from 'react';
import type { Components } from 'react-markdown';
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
import { DocumentTableOfContents } from '@/components/document-table-of-contents';
import { markdownTabComponents } from '@/components/markdown-tabs';
import { NamespaceNavigation } from '@/components/namespace-navigation';
import { usePersistedBoolean } from '@/hooks/use-persisted-boolean';
import { useSyncedScroll } from '@/hooks/use-synced-scroll';
import { documentHref, visibilityLabels } from '@/lib/document';
import { createRelativeLinkComponents } from '@/lib/relative-links';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

import type {
    DocumentNavItem,
    DocumentNavNode,
    DocumentPermissions,
    DocumentWithUser,
} from '@/types';

type ShowDocumentProps = {
    document: DocumentWithUser;
    namespaceDocuments: DocumentNavItem[];
    namespaceNavigation: DocumentNavNode[];
    can: DocumentPermissions;
};

export default function ShowDocument({
    document,
    namespaceDocuments,
    namespaceNavigation,
    can,
}: ShowDocumentProps) {
    const { __ } = lang();
    const { auth } = usePage().props;
    const namespace = document.namespace;
    const [showSource, setShowSource] = useState(false);
    const hasSource = Boolean(document.source_content);
    const hasNamespaceNav = Boolean(namespace) && namespaceDocuments.length > 0;
    const documentReader = hasNamespaceNav;
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
    const contentPaneRef = useRef<HTMLDivElement>(null);
    const rightPanelRef = useRef<HTMLElement>(null);
    const navigationFrameRef = useRef<number | null>(null);
    const splitViewVisible = Boolean(document.source_content) && showSource;

    useSyncedScroll(translationPaneRef, sourcePaneRef, {
        enabled: splitViewVisible,
    });

    useEffect(
        () => () => {
            if (navigationFrameRef.current !== null) {
                cancelAnimationFrame(navigationFrameRef.current);
            }
        },
        [],
    );

    function handleDocumentNavigate(documentId: number) {
        setShowSource(false);

        if (navigationFrameRef.current !== null) {
            cancelAnimationFrame(navigationFrameRef.current);
        }

        navigationFrameRef.current = requestAnimationFrame(() => {
            navigationFrameRef.current = null;

            if (
                contentPaneRef.current?.dataset.documentId !==
                String(documentId)
            ) {
                return;
            }

            contentPaneRef.current.scrollTop = 0;
            if (rightPanelRef.current) {
                rightPanelRef.current.scrollTop = 0;
            }
        });
    }

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
    // the missing page, the same convention as e.g. Wikipedia. A signed-
    // out reader can't create anything there (the route requires auth),
    // so they'd just hit a login wall instead of a 404 — send them to the
    // document URL directly instead, which 404s like any other missing
    // page.
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
                return {
                    url: auth.user
                        ? create(namespace.slug).url
                        : showByPath({ namespace: namespace.slug, path }).url,
                    exists: false,
                };
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
    }, [namespace, namespaceDocuments, auth.user]);

    const ogpEndpoint = fetchOgp().url;

    // Translated markdown is copied from the external site, so root-
    // relative links like "/concepts/system-one" only ever resolved
    // against that site — left alone they'd 404 against this app's own
    // origin. Point them at the sibling document with the same path in
    // this namespace instead, checking namespaceDocuments (already
    // permission-filtered server-side) so an untranslated target renders
    // as a red link (ink-wikilink-broken) rather than looking like a
    // normal, working link that happens to 404 — the same convention as
    // resolveWikilink above, just keyed by path instead of title. A
    // signed-in reader following a red link lands on the create form (a
    // reader can translate the missing page); a guest can't create
    // anything there, so they get the plain document URL, which 404s
    // instead of bouncing them to a login wall.
    const translationLinkComponents = useMemo<Components>(() => {
        if (!namespace) {
            return markdownTabComponents;
        }

        const paths = new Set(
            namespaceDocuments
                .map((item) => item.path)
                .filter((path) => path !== null),
        );

        const linkComponents = createRelativeLinkComponents((path) => {
            if (paths.has(path)) {
                return {
                    url: showByPath({ namespace: namespace.slug, path }).url,
                    exists: true,
                };
            }

            return {
                url: auth.user
                    ? create(namespace.slug, { query: { path } }).url
                    : showByPath({ namespace: namespace.slug, path }).url,
                exists: false,
            };
        });

        return { ...markdownTabComponents, ...linkComponents };
    }, [namespace, namespaceDocuments, auth.user]);

    // The source pane renders the untranslated original, so its root-
    // relative links should resolve exactly as they did on the site it
    // was fetched from — against that site's own origin, not this
    // namespace's paths.
    const sourceLinkComponents = useMemo<Components>(() => {
        if (!document.source_url) {
            return markdownTabComponents;
        }

        const sourceOrigin = new URL(document.source_url).origin;

        return {
            ...markdownTabComponents,
            ...createRelativeLinkComponents(
                (path) => `${sourceOrigin}/${path}`,
            ),
        };
    }, [document.source_url]);

    // Owners get the full "ドキュメント > namespace > title" trail. Everyone
    // else (other logged-in users and guests, i.e. "public mode") only ever
    // reaches a document through its namespace or the public dashboard, so
    // the breadcrumb root is the namespace itself when there is one —
    // regardless of whether that namespace is open or closed. A namespace-
    // less document falls back to the dashboard crumb for logged-in users,
    // or drops the parent crumb entirely for guests, who can't reach it.
    const titleCrumb = { title: document.title, href: documentHref(document) };

    setLayoutProps({
        wide: true,
        documentReader,
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

            <main
                className={cn(
                    'grid min-w-0 gap-4 p-4',
                    documentReader &&
                        'lg:min-h-0 lg:flex-1 lg:grid-rows-[auto_minmax(0,1fr)] lg:overflow-hidden',
                )}
            >
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

                <Card
                    className={cn(
                        'min-w-0',
                        documentReader && 'lg:min-h-0 lg:overflow-hidden',
                    )}
                >
                    <CardContent
                        className={cn(documentReader && 'lg:min-h-0 lg:flex-1')}
                    >
                        <div
                            className={cn(
                                'lg:flex lg:items-start lg:gap-6',
                                documentReader &&
                                    'lg:h-full lg:min-h-0 lg:items-stretch',
                            )}
                        >
                            {hasNamespaceNav && showNav && namespace && (
                                <aside
                                    className="mb-4 max-h-[calc(100dvh-2rem)] self-start overflow-y-auto lg:mb-0 lg:h-full lg:max-h-none lg:w-64 lg:shrink-0 lg:overscroll-contain"
                                    scroll-region=""
                                >
                                    <NamespaceNavigation
                                        key={namespace.slug}
                                        namespace={namespace}
                                        nodes={namespaceNavigation}
                                        currentDocumentId={document.id}
                                        onDocumentNavigate={
                                            handleDocumentNavigate
                                        }
                                    />
                                </aside>
                            )}

                            <div
                                ref={contentPaneRef}
                                data-document-id={document.id}
                                scroll-region=""
                                className={cn(
                                    'min-w-0 lg:flex-1',
                                    documentReader &&
                                        'lg:min-h-0 lg:overflow-y-auto lg:overscroll-contain',
                                )}
                            >
                                <div
                                    key={document.id}
                                    className={cn(
                                        documentReader && 'lg:h-full',
                                    )}
                                >
                                    {document.source_content && showSource ? (
                                        <div
                                            className={cn(
                                                'grid gap-4 md:grid-cols-2',
                                                documentReader &&
                                                    'lg:h-full lg:min-h-0',
                                            )}
                                        >
                                            <div
                                                className={cn(
                                                    'rounded-md border',
                                                    documentReader &&
                                                        'lg:flex lg:min-h-0 lg:flex-col lg:overflow-hidden',
                                                )}
                                            >
                                                <p className="border-b bg-muted/40 px-4 py-2 text-xs font-medium text-muted-foreground">
                                                    {__('Translation')}
                                                </p>
                                                <div
                                                    ref={translationPaneRef}
                                                    scroll-region=""
                                                    className={cn(
                                                        'max-h-[calc(100dvh-2rem)] overflow-y-auto p-4',
                                                        documentReader &&
                                                            'lg:max-h-none lg:min-h-0 lg:flex-1 lg:overscroll-contain',
                                                    )}
                                                >
                                                    <InkstreamMarkdown
                                                        ogpEndpoint={
                                                            ogpEndpoint
                                                        }
                                                        resolveWikilink={
                                                            resolveWikilink
                                                        }
                                                        components={
                                                            translationLinkComponents
                                                        }
                                                    >
                                                        {document.content}
                                                    </InkstreamMarkdown>
                                                </div>
                                            </div>
                                            <div
                                                className={cn(
                                                    'rounded-md border',
                                                    documentReader &&
                                                        'lg:flex lg:min-h-0 lg:flex-col lg:overflow-hidden',
                                                )}
                                            >
                                                <p className="border-b bg-muted/40 px-4 py-2 text-xs font-medium text-muted-foreground">
                                                    {__('Original')}
                                                </p>
                                                <div
                                                    ref={sourcePaneRef}
                                                    scroll-region=""
                                                    className={cn(
                                                        'max-h-[calc(100dvh-2rem)] overflow-y-auto p-4',
                                                        documentReader &&
                                                            'lg:max-h-none lg:min-h-0 lg:flex-1 lg:overscroll-contain',
                                                    )}
                                                >
                                                    <InkstreamMarkdown
                                                        ogpEndpoint={
                                                            ogpEndpoint
                                                        }
                                                        components={
                                                            sourceLinkComponents
                                                        }
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
                                            components={
                                                translationLinkComponents
                                            }
                                        >
                                            {document.content}
                                        </InkstreamMarkdown>
                                    )}
                                </div>
                            </div>

                            {showRightPanel && (
                                <aside
                                    ref={rightPanelRef}
                                    scroll-region=""
                                    className={cn(
                                        'mt-4 max-h-[calc(100dvh-2rem)] self-start overflow-y-auto lg:sticky lg:top-4 lg:mt-0 lg:w-80 lg:shrink-0',
                                        documentReader &&
                                            'lg:static lg:h-full lg:max-h-none lg:overscroll-contain',
                                    )}
                                >
                                    <div
                                        key={document.id}
                                        className="flex flex-col gap-4"
                                    >
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
                                                            document.canonical_url ??
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
                                            <DocumentTableOfContents
                                                headings={headings}
                                                contentPaneRef={contentPaneRef}
                                                scrollContainerRef={
                                                    rightPanelRef
                                                }
                                            />
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
