import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import {
    create as createDocument,
    index,
    show as showDocument,
    showByPath,
} from '@/actions/App/Http/Controllers/DocumentController';
import { show } from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DocumentMeta } from '@/components/document-meta';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { visibilityLabels } from '@/lib/document';

import type { DocumentListItem, DocumentNamespace } from '@/types';
type ShowNamespaceProps = {
    namespace: Pick<DocumentNamespace, 'id' | 'slug' | 'name' | 'source_url'>;
    documents: Pick<
        DocumentListItem,
        | 'id'
        | 'title'
        | 'visibility'
        | 'created_at'
        | 'user'
        | 'likes_count'
        | 'path'
    >[];
};

export default function ShowNamespace({
    namespace,
    documents: documentList,
}: ShowNamespaceProps) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'ドキュメント',
                href: index(),
            },
            {
                title: namespace.name,
                href: show(namespace.slug),
            },
        ],
    });

    return (
        <>
            <Head title={namespace.name} />
            <main className="p-4">
                <div className="mb-6 flex items-start justify-between gap-4">
                    <div className="grid gap-1">
                        <h1 className="text-xl font-semibold">
                            {namespace.name}
                        </h1>
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
                    <Button asChild variant="outline" className="shrink-0">
                        <Link href={createDocument(namespace.slug)}>
                            新規記事
                        </Link>
                    </Button>
                </div>

                {documentList.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                このネームスペースにはまだドキュメントがありません
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
                                        <CardHeader className="flex-row items-center justify-between gap-4">
                                            <div className="grid gap-1">
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
                                                    createdAt={
                                                        document.created_at
                                                    }
                                                />
                                            </div>
                                            <div className="flex shrink-0 items-center gap-1 text-sm text-muted-foreground">
                                                <Heart className="size-4" />
                                                {document.likes_count}
                                            </div>
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
