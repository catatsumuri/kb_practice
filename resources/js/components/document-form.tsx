import { Form, Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { RouteFormDefinition } from '@/wayfinder';

type DocumentFormValues = {
    title: string;
    content: string;
};

type LinkHref = ComponentProps<typeof Link>['href'];

type DocumentFormProps = {
    title: string;
    description: string;
    form: RouteFormDefinition<'post'>;
    cancelHref: LinkHref;
    submitLabel: string;
    defaultValues?: DocumentFormValues;
};

export function DocumentForm({
    title,
    description,
    form,
    cancelHref,
    submitLabel,
    defaultValues,
}: DocumentFormProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>
                <Form {...form} className="grid gap-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">タイトル</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    defaultValue={defaultValues?.title}
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
                                    defaultValue={defaultValues?.content}
                                    aria-describedby="content-help"
                                    aria-invalid={Boolean(errors.content)}
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
                                    <Link href={cancelHref}>キャンセル</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {submitLabel}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
