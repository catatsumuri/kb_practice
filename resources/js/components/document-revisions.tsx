import { router } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { restoreRevision } from '@/actions/App/Http/Controllers/DocumentController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Spinner } from '@/components/ui/spinner';
import { TextDiff } from '@/components/text-diff';
import { formatDateTime } from '@/lib/utils';
import type { Document, DocumentRevision } from '@/types';

type DocumentRevisionsProps = {
    document: Pick<Document, 'id' | 'content'>;
    revisions: DocumentRevision[];
};

export function DocumentRevisions({
    document,
    revisions,
}: DocumentRevisionsProps) {
    const [restoringId, setRestoringId] = useState<number | null>(null);

    if (revisions.length === 0) {
        return null;
    }

    function handleRestore(revision: DocumentRevision) {
        router.post(
            restoreRevision({ document: document.id, revision: revision.id }),
            {},
            {
                preserveScroll: true,
                onStart: () => setRestoringId(revision.id),
                onFinish: () => setRestoringId(null),
            },
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>編集履歴</CardTitle>
                <CardDescription>
                    過去に上書きされた本文を差分で確認し、必要であれば復元できます。
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-2">
                {revisions.map((revision) => (
                    <Collapsible key={revision.id} className="rounded-md border">
                        <div className="flex flex-wrap items-center justify-between gap-2 p-3">
                            <CollapsibleTrigger className="flex items-center gap-2 text-sm font-medium">
                                <ChevronsUpDown className="size-4 shrink-0" />
                                <span>{revision.title}</span>
                                <span className="font-normal text-muted-foreground">
                                    {formatDateTime(revision.created_at)}
                                    {revision.user
                                        ? ` ・ ${revision.user.name}`
                                        : ''}
                                </span>
                            </CollapsibleTrigger>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                disabled={restoringId === revision.id}
                                onClick={() => handleRestore(revision)}
                                className="gap-2"
                            >
                                {restoringId === revision.id && <Spinner />}
                                この版を復元
                            </Button>
                        </div>
                        <CollapsibleContent className="border-t p-3">
                            <TextDiff
                                oldText={revision.content}
                                newText={document.content}
                                leftLabel="この版"
                                rightLabel="現在の本文"
                            />
                        </CollapsibleContent>
                    </Collapsible>
                ))}
            </CardContent>
        </Card>
    );
}
