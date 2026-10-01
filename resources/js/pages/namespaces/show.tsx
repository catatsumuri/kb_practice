import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';
import {
    create as createDocument,
    index,
    show as showDocument,
    showByPath,
} from '@/actions/App/Http/Controllers/DocumentController';
import {
    checkSources,
    edit as editNamespace,
    show,
} from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { Badge } from '@/components/ui/badge';
import { index as backups } from '@/actions/App/Http/Controllers/NamespaceBackupController';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { visibilityLabels } from '@/lib/document';
import { formatDate } from '@/lib/utils';

import type { DocumentNamespace, DocumentWithUser } from '@/types';
type DocumentFilter = 'all' | 'updated' | 'untranslated';

type ShowNamespaceProps = {
    namespace: Pick<
        DocumentNamespace,
        'id' | 'slug' | 'name' | 'source_url' | 'owner_user_id' | 'is_public'
    >;
    documents: (Pick<
        DocumentWithUser,
        'id' | 'title' | 'visibility' | 'created_at' | 'user' | 'path'
    > & {
        adopted_source_snapshot: { fetched_at: string } | null;
        has_pending_source_update: boolean;
        is_untranslated: boolean;
    })[];
};

export default function ShowNamespace({
    namespace,
    documents: allDocuments,
}: ShowNamespaceProps) {
    const { auth } = usePage().props;
    const isOwner = auth.user?.id === namespace.owner_user_id;
    const [checking, setChecking] = useState(false);
    const [filter, setFilter] = useState<DocumentFilter>('all');
    const filters: { value: DocumentFilter; label: string; count: number }[] = [
        { value: 'all', label: 'すべて', count: allDocuments.length },
        {
            value: 'updated',
            label: '更新あり',
            count: allDocuments.filter(
                (document) => document.has_pending_source_update,
            ).length,
        },
        {
            value: 'untranslated',
            label: '未翻訳',
            count: allDocuments.filter((document) => document.is_untranslated)
                .length,
        },
    ];
    const activeFilter =
        filters.find(({ value, count }) => value === filter && count > 0)
            ?.value ?? 'all';
    const showFilters =
        isOwner &&
        filters.some(({ value, count }) => value !== 'all' && count > 0);
    const documentList = allDocuments.filter((document) => {
        if (!isOwner || activeFilter === 'all') {
            return true;
        }

        return activeFilter === 'updated'
            ? document.has_pending_source_update
            : document.is_untranslated;
    });

    function handleCheckSources() {
        router.post(
            checkSources(namespace.slug),
            {},
            {
                preserveScroll: true,
                onStart: () => setChecking(true),
                onFinish: () => setChecking(false),
            },
        );
    }
    const rowGrid = isOwner
        ? 'grid items-center gap-4 sm:grid-cols-[minmax(0,1fr)_6rem_8rem_10rem_10rem]'
        : 'grid items-center gap-4 sm:grid-cols-[minmax(0,1fr)_8rem_10rem_10rem]';

    // Non-owners (including guests, i.e. "public mode") reach this page
    // directly, so the namespace itself is the breadcrumb root regardless
    // of whether it's open or closed. Only the owner gets the "ドキュメント"
    // level above it.
    setLayoutProps({
        wide: true,
        breadcrumbs: isOwner
            ? [
                  { title: 'ドキュメント', href: index() },
                  { title: namespace.name, href: show(namespace.slug) },
              ]
            : [{ title: namespace.name, href: show(namespace.slug) }],
    });

    return (
        <>
            <Head title={namespace.name} />
            <main className="p-4">
                <div className="mb-6 flex items-start justify-between gap-4">
                    <div className="grid gap-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-semibold">
                                {namespace.name}
                            </h1>
                            {isOwner && (
                                <Badge variant="secondary">
                                    {namespace.is_public ? '公開' : '非公開'}
                                </Badge>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">
                            /{namespace.slug}
                        </p>
                        {namespace.source_url && (
                            <a
                                href={namespace.source_url}
                                target="_blank"
                                rel="noreferrer"
                                className="text-sm text-muted-foreground underline underline-offset-2"
                            >
                                {namespace.source_url}
                            </a>
                        )}
                    </div>
                    {isOwner && (
                        <div className="flex shrink-0 flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={checking}
                                onClick={handleCheckSources}
                                className="gap-2"
                            >
                                {checking ? (
                                    <Spinner />
                                ) : (
                                    <RefreshCw className="size-4" />
                                )}
                                ソース更新を確認
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={backups(namespace.slug)}>
                                    バックアップ
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={editNamespace(namespace.slug)}>
                                    設定を編集
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={createDocument(namespace.slug)}>
                                    新規記事
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>

                {showFilters && (
                    <div className="mb-3 flex gap-2">
                        {filters.map(({ value, label, count }) => (
                            <Button
                                key={value}
                                type="button"
                                size="sm"
                                variant={
                                    activeFilter === value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => setFilter(value)}
                            >
                                {label} ({count})
                            </Button>
                        ))}
                    </div>
                )}

                {documentList.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {isOwner
                                    ? 'このネームスペースにはまだドキュメントがありません'
                                    : '公開されているドキュメントはありません'}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                ) : (
                    <div className="overflow-hidden rounded-lg border">
                        <div
                            className={`${rowGrid} border-b bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground`}
                        >
                            <span>タイトル</span>
                            {isOwner && <span>公開範囲</span>}
                            <span className="hidden sm:inline">作成者</span>
                            <span className="hidden sm:inline">作成日</span>
                            <span className="hidden sm:inline">
                                ソース更新日
                            </span>
                        </div>
                        <ul className="divide-y">
                            {documentList.map((document) => (
                                <li key={document.id}>
                                    <Link
                                        href={
                                            document.path
                                                ? showByPath({
                                                      namespace: namespace.slug,
                                                      path: document.path,
                                                  })
                                                : showDocument(document.id)
                                        }
                                        prefetch
                                        className={`${rowGrid} px-4 py-3 text-sm transition-colors hover:bg-muted/50`}
                                    >
                                        <div className="grid min-w-0 gap-0.5">
                                            <span className="flex items-center gap-2">
                                                <span className="truncate font-medium">
                                                    {document.title}
                                                </span>
                                                {isOwner &&
                                                    document.has_pending_source_update && (
                                                        <Badge className="shrink-0">
                                                            更新あり
                                                        </Badge>
                                                    )}
                                                {isOwner &&
                                                    document.is_untranslated && (
                                                        <Badge
                                                            variant="outline"
                                                            className="shrink-0"
                                                        >
                                                            未翻訳
                                                        </Badge>
                                                    )}
                                            </span>
                                            {document.path && (
                                                <span className="truncate text-xs text-muted-foreground">
                                                    /{namespace.slug}/
                                                    {document.path}
                                                </span>
                                            )}
                                        </div>
                                        {isOwner && (
                                            <Badge
                                                variant="secondary"
                                                className="w-fit"
                                            >
                                                {
                                                    visibilityLabels[
                                                        document.visibility
                                                    ]
                                                }
                                            </Badge>
                                        )}
                                        <span className="hidden truncate text-muted-foreground sm:inline">
                                            {document.user.name}
                                        </span>
                                        <time
                                            dateTime={document.created_at}
                                            className="hidden text-muted-foreground sm:inline"
                                        >
                                            {formatDate(document.created_at)}
                                        </time>
                                        <span className="hidden text-muted-foreground sm:inline">
                                            {document.adopted_source_snapshot ? (
                                                <time
                                                    dateTime={
                                                        document
                                                            .adopted_source_snapshot
                                                            .fetched_at
                                                    }
                                                >
                                                    {formatDate(
                                                        document
                                                            .adopted_source_snapshot
                                                            .fetched_at,
                                                    )}
                                                </time>
                                            ) : (
                                                '—'
                                            )}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </main>
        </>
    );
}
