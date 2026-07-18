import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/DocumentController';
import { MarkdownContent } from '@/components/markdown-content';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { formatDate } from '@/lib/utils';
import type { DocumentWithUser } from '@/types';

type ShowDocumentProps = {
    document: DocumentWithUser;
};

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
                <div className="flex items-center justify-between gap-4">
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
                                    ドキュメントを削除しますか？
                                </DialogTitle>
                                <DialogDescription>
                                    「{document.title}
                                    」を削除します。この操作は取り消せません。
                                </DialogDescription>
                                <Form {...destroy.form(document.id)}>
                                    {({ processing }) => (
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                >
                                                    キャンセル
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                variant="destructive"
                                                disabled={processing}
                                            >
                                                {processing
                                                    ? '削除中…'
                                                    : '削除する'}
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
                        <CardDescription className="flex flex-wrap gap-x-3 gap-y-1">
                            <span>作成者：{document.user.name}</span>
                            <time dateTime={document.created_at}>
                                {formatDate(document.created_at)}
                            </time>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <MarkdownContent>{document.content}</MarkdownContent>
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
