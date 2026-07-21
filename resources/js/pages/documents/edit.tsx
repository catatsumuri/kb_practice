import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    edit,
    index,
    show,
    update,
} from '@/actions/App/Http/Controllers/DocumentController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Document = {
    id: number;
    title: string;
    content: string;
};

type EditDocumentProps = {
    document: Document;
};

export default function EditDocument({ document }: EditDocumentProps) {
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
            {
                title: '編集',
                href: edit(document.id),
            },
        ],
    });

    return (
        <>
            <Head title={`${document.title}を編集`} />

            <main className="p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>記事の編集</CardTitle>
                        <CardDescription>
                            タイトルとMarkdown形式の本文を編集できます。
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={update(document.id)}
                            className="grid gap-6"
                        >
                            {({ errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="title">タイトル</Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            defaultValue={document.title}
                                            aria-invalid={Boolean(errors.title)}
                                            autoFocus
                                        />
                                        <InputError message={errors.title} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="content">
                                            本文（Markdown）
                                        </Label>
                                        <Textarea
                                            id="content"
                                            name="content"
                                            defaultValue={document.content}
                                            aria-describedby="content-help"
                                            aria-invalid={Boolean(
                                                errors.content,
                                            )}
                                            className="min-h-80 resize-y font-mono leading-6"
                                        />
                                        <p
                                            id="content-help"
                                            className="text-xs text-muted-foreground"
                                        >
                                            見出し、リスト、リンク、表、タスクリストなどのMarkdown記法を使用できます。
                                        </p>
                                        <InputError message={errors.content} />
                                    </div>

                                    <div className="flex items-center justify-end gap-4">
                                        <Button variant="outline" asChild>
                                            <Link href={show(document.id)}>
                                                キャンセル
                                            </Link>
                                        </Button>
                                        <Button type="submit">更新</Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
