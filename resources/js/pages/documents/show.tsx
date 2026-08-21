import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { Heart } from 'lucide-react';
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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn, formatDate } from '@/lib/utils';
import { dashboard } from '@/routes';

import type { DocumentVisibility, DocumentWithUser } from '@/types';

const visibilityLabels: Record<DocumentVisibility, string> = {
    private: '非公開',
    unlisted: '限定公開',
    public: '公開',
};

type ShowDocumentProps = {
    document: DocumentWithUser;
    likesCount: number;
    liked: boolean;
    can: {
        update: boolean;
        delete: boolean;
    };
};

export default function ShowDocument({
    document,
    likesCount,
    liked,
    can,
}: ShowDocumentProps) {
    const returnRoute = can.update ? index() : dashboard();

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
                    <CardHeader>
                        <div className="flex flex-wrap items-center gap-2">
                            <CardTitle>{document.title}</CardTitle>
                            <Badge variant="secondary">
                                {visibilityLabels[document.visibility]}
                            </Badge>
                        </div>
                        <CardDescription className="flex flex-wrap gap-x-3 gap-y-1">
                            <span>作成者：{document.user.name}</span>
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
