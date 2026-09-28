import { router } from '@inertiajs/react';
import { diffLines } from 'diff';
import type { Change } from 'diff';
import { RefreshCw } from 'lucide-react';
import { Fragment, useState } from 'react';
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
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn, formatDateTime } from '@/lib/utils';
import type { Document, DocumentSourceSnapshot } from '@/types';

type SourceFreshnessProps = {
    document: Pick<Document, 'id'> & {
        adopted_source_snapshot: DocumentSourceSnapshot | null;
    };
    pendingSnapshot: DocumentSourceSnapshot | null;
};

type DiffViewMode = 'unified' | 'split';

type SideBySideCell = {
    content: string;
    type: 'unchanged' | 'added' | 'removed' | 'empty';
};

type SideBySideRow = {
    left: SideBySideCell;
    right: SideBySideCell;
};

function splitIntoLines(value: string): string[] {
    const lines = value.split('\n');

    if (lines.length > 0 && lines[lines.length - 1] === '') {
        lines.pop();
    }

    return lines;
}

// Pairs up adjacent removed/added chunks so a replaced block lines up
// side by side instead of stacking all removals above all additions.
function buildSideBySideRows(changes: Change[]): SideBySideRow[] {
    const rows: SideBySideRow[] = [];
    let index = 0;

    while (index < changes.length) {
        const change = changes[index];

        if (!change.added && !change.removed) {
            for (const line of splitIntoLines(change.value)) {
                rows.push({
                    left: { content: line, type: 'unchanged' },
                    right: { content: line, type: 'unchanged' },
                });
            }

            index++;
            continue;
        }

        const removedLines = change.removed ? splitIntoLines(change.value) : [];

        if (change.removed) {
            index++;
        }

        const nextChange = changes[index];
        const addedLines = nextChange?.added
            ? splitIntoLines(nextChange.value)
            : [];

        if (nextChange?.added) {
            index++;
        }

        const rowCount = Math.max(removedLines.length, addedLines.length);

        for (let row = 0; row < rowCount; row++) {
            rows.push({
                left:
                    row < removedLines.length
                        ? { content: removedLines[row], type: 'removed' }
                        : { content: '', type: 'empty' },
                right:
                    row < addedLines.length
                        ? { content: addedLines[row], type: 'added' }
                        : { content: '', type: 'empty' },
            });
        }
    }

    return rows;
}

function diffCellClassName(type: SideBySideCell['type']) {
    return cn(
        'px-2 whitespace-pre-wrap',
        type === 'added' &&
            'bg-green-500/15 text-green-700 dark:text-green-400',
        type === 'removed' && 'bg-red-500/15 text-red-700 dark:text-red-400',
        type === 'empty' && 'bg-muted/40',
    );
}

export function SourceFreshness({
    document,
    pendingSnapshot,
}: SourceFreshnessProps) {
    const [checking, setChecking] = useState(false);
    const [adopting, setAdopting] = useState(false);
    const [viewMode, setViewMode] = useState<DiffViewMode>('unified');

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

    const diff = pendingSnapshot
        ? diffLines(
              document.adopted_source_snapshot?.content ?? '',
              pendingSnapshot.content,
          )
        : [];
    const sideBySideRows = pendingSnapshot ? buildSideBySideRows(diff) : [];

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
                            <ToggleGroup
                                type="single"
                                variant="outline"
                                size="sm"
                                value={viewMode}
                                onValueChange={(value) =>
                                    value && setViewMode(value as DiffViewMode)
                                }
                            >
                                <ToggleGroupItem value="unified">
                                    統合
                                </ToggleGroupItem>
                                <ToggleGroupItem value="split">
                                    左右
                                </ToggleGroupItem>
                            </ToggleGroup>
                        </div>
                        <AlertDescription className="w-full">
                            {viewMode === 'unified' ? (
                                <div className="max-h-96 w-full overflow-y-auto rounded-md border bg-muted/30 p-3 font-mono text-xs leading-6">
                                    {diff.map((part, index) => (
                                        <div
                                            key={index}
                                            className={cn(
                                                'whitespace-pre-wrap',
                                                part.added &&
                                                    'bg-green-500/15 text-green-700 dark:text-green-400',
                                                part.removed &&
                                                    'bg-red-500/15 text-red-700 dark:text-red-400',
                                            )}
                                        >
                                            {part.value}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="max-h-96 w-full overflow-y-auto rounded-md border font-mono text-xs leading-6">
                                    <div className="grid grid-cols-2">
                                        <div className="sticky top-0 border-r border-b bg-muted px-2 py-1 font-sans font-medium">
                                            現在の原文
                                        </div>
                                        <div className="sticky top-0 border-b bg-muted px-2 py-1 font-sans font-medium">
                                            新しいバージョン
                                        </div>
                                        {sideBySideRows.map((row, index) => (
                                            <Fragment key={index}>
                                                <div
                                                    className={cn(
                                                        diffCellClassName(
                                                            row.left.type,
                                                        ),
                                                        'border-r',
                                                    )}
                                                >
                                                    {row.left.content || ' '}
                                                </div>
                                                <div
                                                    className={diffCellClassName(
                                                        row.right.type,
                                                    )}
                                                >
                                                    {row.right.content || ' '}
                                                </div>
                                            </Fragment>
                                        ))}
                                    </div>
                                </div>
                            )}
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
