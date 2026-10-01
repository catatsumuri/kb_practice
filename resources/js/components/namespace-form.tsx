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
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { RouteFormDefinition } from '@/wayfinder';

type LinkHref = ComponentProps<typeof Link>['href'];

type NamespaceFormValues = {
    slug: string;
    name: string;
    source_url: string | null;
    is_public: boolean;
    guest_redirect_path: string | null;
};

type NamespaceFormProps = {
    title: string;
    description: string;
    form: RouteFormDefinition<'post'>;
    cancelHref: LinkHref;
    submitLabel: string;
    defaultValues?: NamespaceFormValues;
};

export function NamespaceForm({
    title,
    description,
    form,
    cancelHref,
    submitLabel,
    defaultValues,
}: NamespaceFormProps) {
    const editingSlug = Boolean(defaultValues);

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
                                    name={editingSlug ? undefined : 'slug'}
                                    defaultValue={defaultValues?.slug}
                                    disabled={editingSlug}
                                    aria-invalid={Boolean(errors.slug)}
                                    autoFocus={!editingSlug}
                                    required={!editingSlug}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {editingSlug
                                        ? '作成後にスラッグは変更できません。'
                                        : '半角英数字とハイフンのみ使用できます。公開URLの一部になります。'}
                                </p>
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">名前</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={defaultValues?.name}
                                    aria-invalid={Boolean(errors.name)}
                                    autoFocus={editingSlug}
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
                                    defaultValue={
                                        defaultValues?.source_url ?? undefined
                                    }
                                    placeholder="https://docs.example.com"
                                    aria-invalid={Boolean(errors.source_url)}
                                />
                                <InputError message={errors.source_url} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center space-x-3">
                                    <Checkbox
                                        id="is_public"
                                        name="is_public"
                                        defaultChecked={
                                            defaultValues?.is_public
                                        }
                                    />
                                    <Label htmlFor="is_public">
                                        このネームスペースを公開する
                                    </Label>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    公開すると、非ログイン者もネームスペースと公開ドキュメントを閲覧できます。
                                </p>
                                <InputError message={errors.is_public} />
                            </div>

                            {editingSlug && (
                                <div className="grid gap-2">
                                    <Label htmlFor="guest_redirect_path">
                                        未ログイン時の転送先（任意）
                                    </Label>
                                    <Input
                                        id="guest_redirect_path"
                                        name="guest_redirect_path"
                                        defaultValue={
                                            defaultValues?.guest_redirect_path ??
                                            ''
                                        }
                                        placeholder="introduction/quickstart"
                                        maxLength={255}
                                        aria-invalid={Boolean(
                                            errors.guest_redirect_path,
                                        )}
                                        aria-describedby="guest-redirect-help"
                                    />
                                    <p
                                        id="guest-redirect-help"
                                        className="text-xs text-muted-foreground"
                                    >
                                        この名前空間の公開記事のパスを入力してください。未ログインで名前空間のルートを開くとその記事へ転送します。空欄なら一覧を表示します。ログイン中は常に一覧を表示します。
                                    </p>
                                    <InputError
                                        message={errors.guest_redirect_path}
                                    />
                                </div>
                            )}

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
