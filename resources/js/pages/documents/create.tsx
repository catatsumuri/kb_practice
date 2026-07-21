import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/DocumentController';
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

export default function CreateDocument() {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'ドキュメント',
                href: index(),
            },
            {
                title: '新規作成',
                href: create(),
            },
        ],
    });

    return (
        <>
            <Head title="記事の新規作成" />

            <main className="p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>記事の新規作成</CardTitle>
                        <CardDescription>
                            タイトルと本文を入力してください。
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form action={store()} className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="title">タイトル</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    autoFocus
                                    required
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="content">本文</Label>
                                <Textarea
                                    id="content"
                                    name="content"
                                    rows={12}
                                    required
                                />
                            </div>

                            <div className="flex items-center justify-end gap-4">
                                <Button variant="outline" asChild>
                                    <Link href={index()}>キャンセル</Link>
                                </Button>
                                <Button type="submit">保存</Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
