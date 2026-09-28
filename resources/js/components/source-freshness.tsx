import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';
import {
    adoptSourceSnapshot,
    refreshSource,
} from '@/actions/App/Http/Controllers/DocumentController';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { TextDiff } from '@/components/text-diff';
import { formatDateTime } from '@/lib/utils';
import type { Document, DocumentSourceSnapshot } from '@/types';

type SourceFreshnessProps = {
    document: Pick<Document, 'id'> & {
        adopted_source_snapshot: DocumentSourceSnapshot | null;
    };
    pendingSnapshot: DocumentSourceSnapshot | null;
};

export function SourceFreshness({
    document,
    pendingSnapshot,
}: SourceFreshnessProps) {
    const [checking, setChecking] = useState(false);
    const [adopting, setAdopting] = useState(false);

    function handleCheck() {
        router.post(
            refreshSource(document.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setChecking(true),
                onFinish: () => setChecking(false),
            },
        );
    }

    function handleAdopt(snapshot: DocumentSourceSnapshot) {
        router.post(
            adoptSourceSnapshot({
                document: document.id,
                snapshot: snapshot.id,
            }),
            {},
            {
                preserveScroll: true,
                onStart: () => setAdopting(true),
                onFinish: () => setAdopting(false),
            },
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>ソースの鮮度</CardTitle>
                <CardDescription>
                    {document.adopted_source_snapshot
                        ? `最終取得: ${formatDateTime(document.adopted_source_snapshot.fetched_at)}`
                        : '原文はまだ取得していません。'}
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4">
                <div>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={checking}
                        onClick={handleCheck}
                        className="gap-2"
                    >
                        {checking ? (
                            <Spinner />
                        ) : (
                            <RefreshCw className="size-4" />
                        )}
                        最新版を確認
                    </Button>
                </div>

                {pendingSnapshot && (
                    <Alert>
                        <div className="col-start-2 flex flex-wrap items-center justify-between gap-2">
                            <AlertTitle>
                                新しいバージョンがあります（取得:{' '}
                                {formatDateTime(pendingSnapshot.fetched_at)}）
                            </AlertTitle>
                        </div>
                        <AlertDescription className="w-full">
                            <TextDiff
                                oldText={
                                    document.adopted_source_snapshot?.content ??
                                    ''
                                }
                                newText={pendingSnapshot.content}
                                leftLabel="現在の原文"
                                rightLabel="新しいバージョン"
                            />
                            <Button
                                type="button"
                                size="sm"
                                disabled={adopting}
                                onClick={() => handleAdopt(pendingSnapshot)}
                                className="mt-2 gap-2"
                            >
                                {adopting && <Spinner />}
                                この内容を取り込む
                            </Button>
                        </AlertDescription>
                    </Alert>
                )}
            </CardContent>
        </Card>
    );
}
