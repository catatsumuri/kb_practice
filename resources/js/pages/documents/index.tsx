import { Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    create,
    index,
    show,
} from '@/actions/App/Http/Controllers/DocumentController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate } from '@/lib/utils';

import type { Document } from '@/types';
type DocumentsProps = {
    documents: Pick<Document, 'id' | 'title' | 'created_at'>[];
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
                                                <CardDescription>
                                                    <time
                                                        dateTime={
                                                            document.created_at
                                                        }
                                                    >
                                                        {formatDate(
                                                            document.created_at,
                                                        )}
                                                    </time>
                                                </CardDescription>
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
