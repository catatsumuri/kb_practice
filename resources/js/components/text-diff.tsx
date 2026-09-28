import { diffLines } from 'diff';
import type { Change } from 'diff';
import { Fragment, useState } from 'react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';

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

type TextDiffProps = {
    oldText: string;
    newText: string;
    leftLabel: string;
    rightLabel: string;
};

export function TextDiff({ oldText, newText, leftLabel, rightLabel }: TextDiffProps) {
    const [viewMode, setViewMode] = useState<DiffViewMode>('unified');

    const diff = diffLines(oldText, newText);
    const sideBySideRows = buildSideBySideRows(diff);

    return (
        <div className="grid gap-2">
            <div className="flex justify-end">
                <ToggleGroup
                    type="single"
                    variant="outline"
                    size="sm"
                    value={viewMode}
                    onValueChange={(value) =>
                        value && setViewMode(value as DiffViewMode)
                    }
                >
                    <ToggleGroupItem value="unified">統合</ToggleGroupItem>
                    <ToggleGroupItem value="split">左右</ToggleGroupItem>
                </ToggleGroup>
            </div>

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
                            {leftLabel}
                        </div>
                        <div className="sticky top-0 border-b bg-muted px-2 py-1 font-sans font-medium">
                            {rightLabel}
                        </div>
                        {sideBySideRows.map((row, index) => (
                            <Fragment key={index}>
                                <div
                                    className={cn(
                                        diffCellClassName(row.left.type),
                                        'border-r',
                                    )}
                                >
                                    {row.left.content || ' '}
                                </div>
                                <div className={diffCellClassName(row.right.type)}>
                                    {row.right.content || ' '}
                                </div>
                            </Fragment>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
