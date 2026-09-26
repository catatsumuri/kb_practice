import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { lang } from '@erag/lang-sync-inertia/react';
import { Check, ChevronDown, Copy, Heart } from 'lucide-react';
import { useState } from 'react';
import Markdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import {
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/DocumentController';
import {
    destroy as destroyLike,
    store as storeLike,
} from '@/actions/App/Http/Controllers/DocumentLikeController';
import { Badge } from '@/components/ui/badge';
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
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DocumentMeta } from '@/components/document-meta';
import { Input } from '@/components/ui/input';
import { useClipboard } from '@/hooks/use-clipboard';
import { visibilityLabels } from '@/lib/document';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

import type { DocumentPermissions, DocumentWithUser } from '@/types';

type ShowDocumentProps = {
    document: DocumentWithUser;
    likesCount: number;
    liked: boolean;
    can: DocumentPermissions;
    shareUrl: string | null;
};

export default function ShowDocument({
    document,
    likesCount,
    liked,
    can,
    shareUrl,
}: ShowDocumentProps) {
    const { __ } = lang();
    const returnRoute = can.update ? index() : dashboard();
    const [copiedText, copy] = useClipboard();
    const CopyIcon = copiedText === shareUrl ? Check : Copy;
    const [showSource, setShowSource] = useState(false);
    const hasSource = Boolean(document.source_content);

    function toggleLike() {
        const nextLiked = !liked;

        // Only the like count/state are re-fetched here: `document` (with
        // its markdown content) is left out of the partial reload so
        // liking doesn't re-transfer and re-render the whole page.
        const optimisticRouter = router.optimistic<{
            likesCount: number;
            liked: boolean;
        }>((props) => ({
            likesCount: props.likesCount + (nextLiked ? 1 : -1),
            liked: nextLiked,
        }));

        const options = {
            only: ['likesCount', 'liked'],
            preserveScroll: true,
        };

        if (nextLiked) {
            optimisticRouter.post(storeLike.url(document.id), {}, options);
        } else {
            optimisticRouter.delete(destroyLike.url(document.id), options);
        }
    }

    setLayoutProps({
        breadcrumbs: [
            {
                title: can.update ? 'ドキュメント' : 'ダッシュボード',
                href: returnRoute,
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
                        <Link href={returnRoute}>
                            {can.update ? '一覧へ戻る' : 'ダッシュボードへ戻る'}
                        </Link>
                    </Button>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant={liked ? 'default' : 'outline'}
                            onClick={toggleLike}
                            className="gap-2"
                        >
                            <Heart className={cn(liked && 'fill-current')} />
                            {likesCount}
                        </Button>

                        {(can.update || can.delete) && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={edit(document.id)}>編集</Link>
                                </Button>

                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                        >
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
                            </>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader className="sticky top-0 z-20 border-b bg-card py-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <CardTitle>{document.title}</CardTitle>
                            <Badge variant="secondary">
                                {visibilityLabels[document.visibility]}
                            </Badge>
                        </div>
                        <DocumentMeta
                            author={document.user.name}
                            createdAt={document.created_at}
                        />
                        {document.source_url && (
                            <p className="text-sm text-muted-foreground">
                                {__('Translated from:')}{' '}
                                <a
                                    href={document.source_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="underline"
                                >
                                    {document.source_title ??
                                        document.source_url}
                                </a>
                                {document.source_author && (
                                    <>
                                        {' '}
                                        {__('(:author)', {
                                            author: document.source_author,
                                        })}
                                    </>
                                )}
                                {hasSource && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="ml-2 h-auto gap-1 px-2 py-0.5"
                                        onClick={() =>
                                            setShowSource((open) => !open)
                                        }
                                    >
                                        <ChevronDown
                                            className={cn(
                                                'size-4 transition-transform',
                                                showSource && 'rotate-180',
                                            )}
                                        />
                                        {showSource
                                            ? __('Hide original')
                                            : __('Show original')}
                                    </Button>
                                )}
                            </p>
                        )}
                    </CardHeader>
                    <CardContent>
                        {shareUrl && (
                            <div className="mb-4 flex flex-wrap items-center gap-2">
                                <Input
                                    type="text"
                                    readOnly
                                    value={shareUrl}
                                    onFocus={(event) => event.target.select()}
                                    className="max-w-md"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    onClick={() => copy(shareUrl)}
                                >
                                    <CopyIcon />
                                </Button>
                            </div>
                        )}

                        {hasSource && showSource ? (
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="rounded-md border">
                                    <p className="border-b bg-muted/40 px-4 py-2 text-xs font-medium text-muted-foreground">
                                        {__('Translation')}
                                    </p>
                                    <div className="markdown-content p-4">
                                        <Markdown remarkPlugins={[remarkGfm]}>
                                            {document.content}
                                        </Markdown>
                                    </div>
                                </div>
                                <div className="rounded-md border">
                                    <p className="border-b bg-muted/40 px-4 py-2 text-xs font-medium text-muted-foreground">
                                        {__('Original')}
                                    </p>
                                    <div className="markdown-content p-4">
                                        <Markdown remarkPlugins={[remarkGfm]}>
                                            {document.source_content}
                                        </Markdown>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="markdown-content">
                                <Markdown remarkPlugins={[remarkGfm]}>
                                    {document.content}
                                </Markdown>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
