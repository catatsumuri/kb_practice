import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import Markdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import {
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/DocumentController';
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

type ShowDocumentProps = {
    document: Document;
};

function formatDate(date: string) {
    return new Intl.DateTimeFormat('ja-JP', {
        dateStyle: 'long',
        timeZone: 'Asia/Tokyo',
    }).format(new Date(date));
}

export default function ShowDocument({ document }: ShowDocumentProps) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'ドキュメント',
                href: index(),
            },
            {
                title: document.title,
                href: show(document.id),
            },
        ],
    });

    return (
        <>
            <Head title={document.title} />

            <main className="grid gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link href={index()}>一覧へ戻る</Link>
                    </Button>

                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(document.id)}>編集</Link>
                        </Button>

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button type="button" variant="destructive">
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
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{document.title}</CardTitle>
                        <CardDescription>
                            <time dateTime={document.created_at}>
                                {formatDate(document.created_at)}
                            </time>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="markdown-content">
                            <Markdown remarkPlugins={[remarkGfm]}>
                                {document.content}
                            </Markdown>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
