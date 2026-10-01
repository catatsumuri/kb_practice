import { lang } from '@erag/lang-sync-inertia/react';
import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowRight, FolderOpen, Search } from 'lucide-react';
import { show as showNamespace } from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { documentHref } from '@/lib/document';
import { formatDate } from '@/lib/utils';
import { dashboard } from '@/routes';

type LibraryDocument = {
    id: number;
    path: string | null;
    title: string;
    updated_at: string;
    user: { name: string };
    namespace: { slug: string; name: string } | null;
};

type DashboardProps = {
    filters: {
        q: string;
        view: 'overview' | 'all';
        scope: 'all' | 'standalone';
    };
    collections: {
        id: number;
        slug: string;
        name: string;
        documents_count: number;
        owner: { name: string };
    }[];
    recentDocuments: LibraryDocument[];
    standaloneDocuments: LibraryDocument[];
    standaloneCount: number;
    documents: {
        data: LibraryDocument[];
        total: number;
        current_page: number;
        last_page: number;
    } | null;
};

function DocumentList({ documents }: { documents: LibraryDocument[] }) {
    return (
        <ul className="divide-y rounded-lg border bg-card">
            {documents.map((document) => (
                <li key={document.id}>
                    <Link
                        href={documentHref(document)}
                        prefetch
                        className="flex flex-col gap-2 rounded-md px-4 py-3 transition-colors hover:bg-muted/50 focus-visible:outline-2 focus-visible:outline-ring sm:flex-row sm:items-center sm:justify-between sm:gap-4"
                    >
                        <div className="min-w-0 space-y-1">
                            <p className="text-xs text-muted-foreground">
                                {document.namespace?.name ?? '単独文書'} ·{' '}
                                {document.user.name}
                            </p>
                            <p className="text-sm font-medium break-words">
                                {document.title}
                            </p>
                        </div>
                        <time
                            dateTime={document.updated_at}
                            className="shrink-0 text-xs text-muted-foreground"
                        >
                            更新 {formatDate(document.updated_at)}
                        </time>
                    </Link>
                </li>
            ))}
        </ul>
    );
}

export default function Dashboard({
    filters,
    collections,
    recentDocuments,
    standaloneDocuments,
    standaloneCount,
    documents,
}: DashboardProps) {
    const { __ } = lang();

    setLayoutProps({
        breadcrumbs: [{ title: __('Dashboard'), href: dashboard() }],
    });

    function listHref(page = 1) {
        return dashboard({
            query: { view: 'all', scope: filters.scope, q: filters.q, page },
        });
    }

    return (
        <>
            <Head title="公開ライブラリー" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 md:p-6">
                <header className="space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        公開ライブラリー
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        コレクションから読むか、公開文書を検索できます。
                    </p>
                </header>

                <Form
                    action={dashboard()}
                    key={filters.q + ':' + filters.scope}
                    options={{ preserveScroll: true }}
                    className="space-y-2"
                >
                    {({ processing, errors }) => (
                        <>
                            <label htmlFor="library-search" className="sr-only">
                                公開文書を検索
                            </label>
                            <div className="flex gap-2">
                                <div className="relative flex-1">
                                    <Search
                                        aria-hidden="true"
                                        className="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                                    />
                                    <Input
                                        id="library-search"
                                        name="q"
                                        defaultValue={filters.q}
                                        maxLength={200}
                                        placeholder="タイトル・文書パスで検索"
                                        className="pl-9"
                                        aria-invalid={Boolean(errors.q)}
                                        aria-describedby={
                                            errors.q
                                                ? 'search-error'
                                                : undefined
                                        }
                                    />
                                </div>
                                <input type="hidden" name="view" value="all" />
                                <input
                                    type="hidden"
                                    name="scope"
                                    value={filters.scope}
                                />
                                <Button type="submit" disabled={processing}>
                                    検索
                                </Button>
                            </div>
                            {errors.q && (
                                <p
                                    id="search-error"
                                    className="text-sm text-destructive"
                                >
                                    {errors.q}
                                </p>
                            )}
                        </>
                    )}
                </Form>

                {filters.view === 'overview' ? (
                    <>
                        <section
                            aria-labelledby="collections-heading"
                            className="space-y-3"
                        >
                            <h2
                                id="collections-heading"
                                className="text-lg font-semibold"
                            >
                                コレクション
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                名前空間ごとにまとめられた公開文書です。
                            </p>
                            {collections.length ? (
                                <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {collections.map((collection) => (
                                        <li key={collection.id}>
                                            <Link
                                                href={showNamespace(
                                                    collection.slug,
                                                )}
                                                prefetch
                                                className="block h-full rounded-lg focus-visible:outline-2 focus-visible:outline-ring"
                                            >
                                                <Card className="h-full transition-colors hover:bg-muted/50">
                                                    <CardHeader className="gap-3">
                                                        <FolderOpen
                                                            aria-hidden="true"
                                                            className="size-5 text-muted-foreground"
                                                        />
                                                        <CardTitle className="leading-snug break-words">
                                                            {collection.name}
                                                        </CardTitle>
                                                        <p className="text-sm text-muted-foreground">
                                                            {
                                                                collection.documents_count
                                                            }
                                                            件の公開文書
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {
                                                                collection.owner
                                                                    .name
                                                            }{' '}
                                                            · /{collection.slug}
                                                        </p>
                                                    </CardHeader>
                                                </Card>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="rounded-lg border p-4 text-sm text-muted-foreground">
                                    公開文書のあるコレクションはまだありません。
                                </p>
                            )}
                        </section>

                        <section
                            aria-labelledby="recent-heading"
                            className="space-y-3"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <h2
                                    id="recent-heading"
                                    className="text-lg font-semibold"
                                >
                                    最近更新された文書
                                </h2>
                                <Button asChild variant="ghost" size="sm">
                                    <Link
                                        href={dashboard({
                                            query: { view: 'all' },
                                        })}
                                    >
                                        すべて見る
                                        <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            </div>
                            {recentDocuments.length ? (
                                <DocumentList documents={recentDocuments} />
                            ) : (
                                <p className="rounded-lg border p-4 text-sm text-muted-foreground">
                                    公開文書はまだありません。
                                </p>
                            )}
                        </section>

                        <section
                            aria-labelledby="standalone-heading"
                            className="space-y-3"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <h2
                                    id="standalone-heading"
                                    className="text-lg font-semibold"
                                >
                                    単独の公開文書
                                </h2>
                                {standaloneCount > 0 && (
                                    <Button asChild variant="ghost" size="sm">
                                        <Link
                                            href={dashboard({
                                                query: {
                                                    view: 'all',
                                                    scope: 'standalone',
                                                },
                                            })}
                                        >
                                            {standaloneCount}件すべて見る
                                            <ArrowRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                )}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                名前空間に属さない文書です。
                            </p>
                            {standaloneDocuments.length ? (
                                <DocumentList documents={standaloneDocuments} />
                            ) : (
                                <p className="rounded-lg border p-4 text-sm text-muted-foreground">
                                    単独の公開文書はまだありません。
                                </p>
                            )}
                        </section>
                    </>
                ) : (
                    documents && (
                        <section
                            aria-labelledby="results-heading"
                            className="space-y-4"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div className="space-y-1">
                                    <h2
                                        id="results-heading"
                                        className="text-lg font-semibold"
                                    >
                                        {filters.q
                                            ? '検索結果'
                                            : filters.scope === 'standalone'
                                              ? '単独の公開文書'
                                              : 'すべての公開文書'}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {documents.total}件 · 更新日の新しい順
                                        {filters.q &&
                                            ' · 「' + filters.q + '」'}
                                    </p>
                                </div>
                                <Button asChild variant="outline" size="sm">
                                    <Link href={dashboard()}>
                                        ライブラリーに戻る
                                    </Link>
                                </Button>
                            </div>
                            {documents.data.length ? (
                                <DocumentList documents={documents.data} />
                            ) : (
                                <p className="rounded-lg border p-4 text-sm text-muted-foreground">
                                    該当する公開文書はありません。検索語を変えてお試しください。
                                </p>
                            )}
                            {documents.last_page > 1 && (
                                <nav
                                    aria-label="文書一覧のページ切り替え"
                                    className="flex items-center justify-between gap-3"
                                >
                                    {documents.current_page > 1 ? (
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                        >
                                            <Link
                                                href={listHref(
                                                    documents.current_page - 1,
                                                )}
                                            >
                                                前へ
                                            </Link>
                                        </Button>
                                    ) : (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled
                                        >
                                            前へ
                                        </Button>
                                    )}
                                    <span className="text-sm text-muted-foreground">
                                        {documents.current_page} /{' '}
                                        {documents.last_page}
                                    </span>
                                    {documents.current_page <
                                    documents.last_page ? (
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                        >
                                            <Link
                                                href={listHref(
                                                    documents.current_page + 1,
                                                )}
                                            >
                                                次へ
                                            </Link>
                                        </Button>
                                    ) : (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled
                                        >
                                            次へ
                                        </Button>
                                    )}
                                </nav>
                            )}
                        </section>
                    )
                )}
            </main>
        </>
    );
}
