import { Form, Link, router } from '@inertiajs/react';
import { Download, PanelRightClose, PanelRightOpen } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ComponentProps } from 'react';
import { fetchSource } from '@/actions/App/Http/Controllers/DocumentController';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { DocumentVisibility } from '@/types';
import { cn } from '@/lib/utils';
import type { RouteFormDefinition } from '@/wayfinder';

type DocumentFormValues = {
    title: string;
    content: string;
    visibility: DocumentVisibility;
    source_url?: string | null;
    source_title?: string | null;
    source_author?: string | null;
    source_content?: string | null;
};

export type FetchedSource = {
    source_url: string;
    source_title: string | null;
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
    allowSourceFetch?: boolean;
    fetchedSource?: FetchedSource | null;
};

export function DocumentForm({
    title,
    description,
    form,
    cancelHref,
    submitLabel,
    defaultValues,
    allowSourceFetch = false,
    fetchedSource,
}: DocumentFormProps) {
    const titleRef = useRef<HTMLInputElement>(null);
    const contentRef = useRef<HTMLTextAreaElement>(null);
    const sourceTitleRef = useRef<HTMLInputElement>(null);
    const [sourceUrl, setSourceUrl] = useState(
        defaultValues?.source_url ?? '',
    );
    const [fetching, setFetching] = useState(false);
    const [fetchError, setFetchError] = useState<string | null>(null);
    const [sourceContent, setSourceContent] = useState(
        defaultValues?.source_content ?? '',
    );
    const [showSourcePreview, setShowSourcePreview] = useState(
        Boolean(defaultValues?.source_content),
    );

    useEffect(() => {
        if (!fetchedSource) {
            return;
        }

        if (contentRef.current) {
            contentRef.current.value = fetchedSource.content;
        }

        setSourceContent(fetchedSource.content);
        setShowSourcePreview(true);

        if (fetchedSource.source_title) {
            if (sourceTitleRef.current && !sourceTitleRef.current.value) {
                sourceTitleRef.current.value = fetchedSource.source_title;
            }

            if (titleRef.current && !titleRef.current.value) {
                titleRef.current.value = fetchedSource.source_title;
            }
        }
    }, [fetchedSource]);

    function handleFetchSource() {
        const url = sourceUrl.trim();

        if (!url) {
            return;
        }

        setFetchError(null);

        router.post(
            fetchSource.url(),
            { source_url: url },
            {
                preserveScroll: true,
                preserveState: true,
                preserveUrl: true,
                onStart: () => setFetching(true),
                onFinish: () => setFetching(false),
                onError: (errors) =>
                    setFetchError(
                        errors.source_url ?? '取得に失敗しました。',
                    ),
            },
        );
    }
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
                            {allowSourceFetch && (
                                <div className="grid gap-4 rounded-md border p-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="source_url">
                                            翻訳元URL（任意）
                                        </Label>
                                        <div className="flex gap-2">
                                            <Input
                                                id="source_url"
                                                name="source_url"
                                                type="url"
                                                value={sourceUrl}
                                                onChange={(event) =>
                                                    setSourceUrl(
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="https://example.com/article.md"
                                                aria-invalid={Boolean(
                                                    errors.source_url,
                                                )}
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                disabled={
                                                    fetching ||
                                                    !sourceUrl.trim()
                                                }
                                                onClick={handleFetchSource}
                                                className="shrink-0 gap-2"
                                            >
                                                {fetching ? (
                                                    <Spinner />
                                                ) : (
                                                    <Download className="size-4" />
                                                )}
                                                取得
                                            </Button>
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            指定すると本文欄に取得した内容を読み込みます。取得後は自由に編集してください。
                                        </p>
                                        <InputError
                                            message={
                                                fetchError ?? errors.source_url
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="source_title">
                                            出典タイトル
                                        </Label>
                                        <Input
                                            id="source_title"
                                            name="source_title"
                                            ref={sourceTitleRef}
                                            defaultValue={
                                                defaultValues?.source_title ??
                                                undefined
                                            }
                                            aria-invalid={Boolean(
                                                errors.source_title,
                                            )}
                                        />
                                        <InputError
                                            message={errors.source_title}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="source_author">
                                            出典著者（任意）
                                        </Label>
                                        <Input
                                            id="source_author"
                                            name="source_author"
                                            defaultValue={
                                                defaultValues?.source_author ??
                                                undefined
                                            }
                                            aria-invalid={Boolean(
                                                errors.source_author,
                                            )}
                                        />
                                        <InputError
                                            message={errors.source_author}
                                        />
                                    </div>

                                    <input
                                        type="hidden"
                                        name="source_content"
                                        value={sourceContent}
                                        readOnly
                                    />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="title">タイトル</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    ref={titleRef}
                                    defaultValue={defaultValues?.title}
                                    aria-invalid={Boolean(errors.title)}
                                    autoFocus
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="visibility">公開範囲</Label>
                                <Select
                                    name="visibility"
                                    defaultValue={
                                        defaultValues?.visibility ?? 'private'
                                    }
                                >
                                    <SelectTrigger
                                        id="visibility"
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            errors.visibility,
                                        )}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="private">
                                            非公開
                                        </SelectItem>
                                        <SelectItem value="unlisted">
                                            限定公開
                                        </SelectItem>
                                        <SelectItem value="public">
                                            公開
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">
                                    公開したドキュメントは全ユーザーの公開一覧に表示されます。
                                </p>
                                <InputError message={errors.visibility} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <Label htmlFor="content">
                                        本文（Markdown）
                                    </Label>
                                    {allowSourceFetch && sourceContent && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="h-auto gap-1 px-2 py-0.5"
                                            onClick={() =>
                                                setShowSourcePreview(
                                                    (open) => !open,
                                                )
                                            }
                                        >
                                            {showSourcePreview ? (
                                                <PanelRightClose className="size-4" />
                                            ) : (
                                                <PanelRightOpen className="size-4" />
                                            )}
                                            {showSourcePreview
                                                ? '原文を非表示'
                                                : '原文を表示'}
                                        </Button>
                                    )}
                                </div>
                                <div
                                    className={cn(
                                        'grid gap-4',
                                        showSourcePreview &&
                                            sourceContent &&
                                            'lg:grid-cols-2',
                                    )}
                                >
                                    <Textarea
                                        id="content"
                                        name="content"
                                        ref={contentRef}
                                        defaultValue={defaultValues?.content}
                                        aria-describedby="content-help"
                                        aria-invalid={Boolean(errors.content)}
                                        className="min-h-80 resize-y font-mono leading-6"
                                    />
                                    {showSourcePreview && sourceContent && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="source_content_preview">
                                                原文（参照用）
                                            </Label>
                                            <Textarea
                                                id="source_content_preview"
                                                readOnly
                                                value={sourceContent}
                                                className="min-h-80 resize-y bg-muted/30 font-mono leading-6"
                                            />
                                        </div>
                                    )}
                                </div>
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
