import { Form, Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { toast } from 'sonner';
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
import type { RouteFormDefinition } from '@/wayfinder';

type LinkHref = ComponentProps<typeof Link>['href'];

type NamespaceFormProps = {
    title: string;
    description: string;
    form: RouteFormDefinition<'post'>;
    cancelHref: LinkHref;
    submitLabel: string;
};

export function NamespaceForm({
    title,
    description,
    form,
    cancelHref,
    submitLabel,
}: NamespaceFormProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    {...form}
                    className="grid gap-6"
                    onError={() => toast.error('入力内容に誤りがあります。')}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="slug">スラッグ</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    aria-invalid={Boolean(errors.slug)}
                                    autoFocus
                                    required
                                />
                                <p className="text-xs text-muted-foreground">
                                    半角英数字とハイフンのみ使用できます。公開URLの一部になります。
                                </p>
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">名前</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    aria-invalid={Boolean(errors.name)}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="source_url">
                                    元サイトURL（任意）
                                </Label>
                                <Input
                                    id="source_url"
                                    name="source_url"
                                    type="url"
                                    placeholder="https://docs.example.com"
                                    aria-invalid={Boolean(errors.source_url)}
                                />
                                <InputError message={errors.source_url} />
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
