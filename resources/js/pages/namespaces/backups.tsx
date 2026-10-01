import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { Download, Trash2 } from 'lucide-react';
import { show } from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import {
    download,
    destroy,
    index,
    store,
} from '@/actions/App/Http/Controllers/NamespaceBackupController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

type Backup = {
    filename: string;
    created_at: string | null;
    description: string | null;
    size_bytes: number;
    document_count: number;
    with_snapshots: boolean;
    with_revisions: boolean;
};

function formatDate(value: string | null): string {
    if (!value || Number.isNaN(Date.parse(value))) {
        return '日時不明';
    }

    return new Intl.DateTimeFormat('ja-JP', {
        timeZone: 'Asia/Tokyo',
        dateStyle: 'medium',
        timeStyle: 'medium',
    }).format(new Date(value));
}

function DeleteBackupDialog({
    namespace,
    filename,
}: {
    namespace: string;
    filename: string;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" variant="destructive">
                    <Trash2 />
                    削除
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>このバックアップを削除しますか？</DialogTitle>
                <DialogDescription>
                    <span className="block break-all">{filename}</span>
                    削除したZIPは元に戻せません。現在の記事は削除されません。
                </DialogDescription>
                <Form
                    {...destroy.form({ namespace, backup: filename })}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <div className="grid gap-4">
                            <InputError message={errors.backup} role="alert" />
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        キャンセル
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {processing ? '削除中…' : '削除する'}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function NamespaceBackups({
    namespace,
    backups,
}: {
    namespace: { slug: string; name: string };
    backups: Backup[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: namespace.name, href: show(namespace.slug) },
            { title: 'バックアップ', href: index(namespace.slug) },
        ],
    });

    return (
        <>
            <Head title={`${namespace.name}のバックアップ`} />
            <main className="mx-auto grid w-full max-w-5xl gap-6 p-4">
                <div className="grid gap-2">
                    <h1 className="text-xl font-semibold">
                        {namespace.name}のバックアップ
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        保存済みのZIPアーカイブをダウンロードできます。日時は日本時間です。
                    </p>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>フルバックアップを作成</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            この名前空間の全記事・原文・変更履歴・原文スナップショットをZIPに保存します。非公開の記事も含まれます。
                        </p>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...store.form(namespace.slug)}
                            resetOnSuccess
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="backup-description">
                                            説明（任意）
                                        </Label>
                                        <Textarea
                                            id="backup-description"
                                            name="description"
                                            placeholder="例：前回からクックブックの翻訳が1本増えました。"
                                            maxLength={2000}
                                            disabled={processing}
                                            aria-invalid={Boolean(
                                                errors.description,
                                            )}
                                            aria-describedby={
                                                errors.description
                                                    ? 'backup-description-error'
                                                    : undefined
                                            }
                                        />
                                        <InputError
                                            id="backup-description-error"
                                            message={errors.description}
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-fit"
                                    >
                                        {processing
                                            ? '作成中…'
                                            : 'フルバックアップを作成'}
                                    </Button>
                                </div>
                            )}
                        </Form>
                    </CardContent>
                </Card>
                {backups.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>バックアップはまだありません</CardTitle>
                        </CardHeader>
                    </Card>
                ) : (
                    <ul className="grid gap-4">
                        {backups.map((backup) => (
                            <li key={backup.filename}>
                                <Card>
                                    <CardHeader className="flex flex-col items-start justify-between gap-3 sm:flex-row">
                                        <div className="grid min-w-0 gap-2">
                                            <CardTitle className="break-all">
                                                {backup.filename}
                                            </CardTitle>
                                            <p className="text-sm text-muted-foreground">
                                                {formatDate(backup.created_at)}{' '}
                                                · {backup.document_count}記事 ·{' '}
                                                {(
                                                    backup.size_bytes / 1024
                                                ).toLocaleString('ja-JP', {
                                                    maximumFractionDigits: 1,
                                                })}{' '}
                                                KiB
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 flex-wrap gap-2">
                                            <Button asChild variant="outline">
                                                <a
                                                    href={download.url({
                                                        namespace:
                                                            namespace.slug,
                                                        backup: backup.filename,
                                                    })}
                                                >
                                                    <Download />
                                                    ダウンロード
                                                </a>
                                            </Button>
                                            <DeleteBackupDialog
                                                namespace={namespace.slug}
                                                filename={backup.filename}
                                            />
                                        </div>
                                    </CardHeader>
                                    <CardContent className="grid gap-3">
                                        <p className="text-sm break-words whitespace-pre-wrap">
                                            {backup.description ||
                                                '説明はありません'}
                                        </p>
                                        <TooltipProvider>
                                            <div className="flex flex-wrap gap-2">
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <button
                                                            type="button"
                                                            className="cursor-help rounded-md focus-visible:outline-2 focus-visible:outline-ring"
                                                        >
                                                            <Badge variant="secondary">
                                                                {backup.with_revisions
                                                                    ? '変更履歴あり'
                                                                    : '変更履歴なし'}
                                                            </Badge>
                                                        </button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        編集や翻訳で上書きされる前のタイトル・本文の履歴です。
                                                        {backup.with_revisions
                                                            ? 'このZIPには保存済みの履歴を含めています。'
                                                            : 'このZIPには過去の履歴を含めていません。現在のタイトル・本文は含まれています。'}
                                                    </TooltipContent>
                                                </Tooltip>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <button
                                                            type="button"
                                                            className="cursor-help rounded-md focus-visible:outline-2 focus-visible:outline-ring"
                                                        >
                                                            <Badge variant="secondary">
                                                                {backup.with_snapshots
                                                                    ? '原文スナップショットあり'
                                                                    : '原文スナップショットなし'}
                                                            </Badge>
                                                        </button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        原文を取得・更新したときに保存した各時点の原文です。
                                                        {backup.with_snapshots
                                                            ? 'このZIPには保存済みの原文スナップショットを含めています。'
                                                            : 'このZIPには原文スナップショットを含めていません。記事に保存されている現在の原文は含まれています。'}
                                                    </TooltipContent>
                                                </Tooltip>
                                            </div>
                                        </TooltipProvider>
                                    </CardContent>
                                </Card>
                            </li>
                        ))}
                    </ul>
                )}
            </main>
        </>
    );
}
