import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import {
    create,
    index,
    show,
} from '@/actions/App/Http/Controllers/DocumentController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DocumentMeta } from '@/components/document-meta';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { visibilityLabels } from '@/lib/document';

import type { DocumentListItem } from '@/types';
type DocumentsProps = {
    documents: Pick<
        DocumentListItem,
        'id' | 'title' | 'visibility' | 'created_at' | 'user' | 'likes_count'
    >[];
};

export default function Documents({ documents: documentList }: DocumentsProps) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'ドキュメント',
                href: index(),
            },
        ],
    });

    return (
        <>
            <Head title="ドキュメント" />
            <main className="p-4">
                <div className="mb-4 flex justify-end">
                    <Button asChild>
                        <Link href={create()}>新規記事</Link>
                    </Button>
                </div>
                {documentList.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>ドキュメントはまだありません</CardTitle>
                            <CardDescription>
                                新規記事から最初のドキュメントを作成できます。
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    <ul className="grid gap-3">
                        {documentList.map((document) => (
                            <li key={document.id}>
                                <Link href={show(document.id)} prefetch>
                                    <Card className="transition-colors hover:bg-muted/50">
                                        <CardHeader className="flex-row items-center justify-between gap-4">
                                            <div className="grid gap-1">
                                                <CardTitle>
                                                    {document.title}
                                                </CardTitle>
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
