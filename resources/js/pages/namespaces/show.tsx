import { Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import {
    create as createDocument,
    index,
    show as showDocument,
    showByPath,
} from '@/actions/App/Http/Controllers/DocumentController';
import {
    edit as editNamespace,
    show,
} from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { Badge } from '@/components/ui/badge';
import { index as backups } from '@/actions/App/Http/Controllers/NamespaceBackupController';
import { Button } from '@/components/ui/button';
import { DocumentMeta } from '@/components/document-meta';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { visibilityLabels } from '@/lib/document';

import type { DocumentNamespace, DocumentWithUser } from '@/types';
type ShowNamespaceProps = {
    namespace: Pick<
        DocumentNamespace,
        'id' | 'slug' | 'name' | 'source_url' | 'owner_user_id' | 'is_public'
    >;
    documents: Pick<
        DocumentWithUser,
        'id' | 'title' | 'visibility' | 'created_at' | 'user' | 'path'
    >[];
};

export default function ShowNamespace({
    namespace,
    documents: documentList,
}: ShowNamespaceProps) {
    const { auth } = usePage().props;
    const isOwner = auth.user?.id === namespace.owner_user_id;

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
                    <ul className="grid gap-3">
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
                                >
                                    <Card className="transition-colors hover:bg-muted/50">
                                        <CardHeader className="grid gap-1">
                                            <CardTitle>
                                                {document.title}
                                            </CardTitle>
                                            {document.path && (
                                                <p className="text-xs text-muted-foreground">
                                                    /{namespace.slug}/
                                                    {document.path}
                                                </p>
                                            )}
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
                                            <DocumentMeta
                                                author={document.user.name}
                                                createdAt={document.created_at}
                                            />
                                        </CardHeader>
                                    </Card>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </main>
        </>
    );
}
