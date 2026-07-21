import { Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    create,
    index,
} from '@/actions/App/Http/Controllers/DocumentController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Document = {
    id: number;
    title: string;
    content: string;
    created_at: string;
};

type DocumentsProps = {
    documents: Document[];
};

function formatDate(date: string) {
    return new Intl.DateTimeFormat('ja-JP', {
        dateStyle: 'long',
        timeZone: 'Asia/Tokyo',
    }).format(new Date(date));
}

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
                <div className="grid gap-4">
                    {documentList.map((document) => (
                        <Card key={document.id}>
                            <CardHeader>
                                <CardTitle>{document.title}</CardTitle>
                                <CardDescription>
                                    <time dateTime={document.created_at}>
                                        {formatDate(document.created_at)}
                                    </time>
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <p className="whitespace-pre-wrap">
                                    {document.content}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </main>
        </>
    );
}
