import {
    ArrowDown,
    ArrowUp,
    CornerDownRight,
    FileText,
    Folder,
    GripVertical,
    Plus,
    Trash2,
} from 'lucide-react';
import { useRef, useState } from 'react';
import type { DragEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

export type NavigationDocumentOption = {
    title: string;
    path: string;
};

type EditorNode = {
    key: number;
    title: string;
    page: string;
    label: string;
    pages: EditorNode[];
};

type NavigationEditorProps = {
    defaultValue: string;
    documents: NavigationDocumentOption[];
    error?: string;
};

const MAX_DEPTH = 5;

function parseNodes(value: unknown, nextKey: () => number): EditorNode[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.flatMap((entry): EditorNode[] => {
        if (typeof entry === 'string') {
            return [
                {
                    key: nextKey(),
                    title: '',
                    page: entry,
                    label: '',
                    pages: [],
                },
            ];
        }

        if (entry === null || typeof entry !== 'object') {
            return [];
        }

        const node = entry as Record<string, unknown>;
        const text = (field: unknown) =>
            typeof field === 'string' ? field : '';

        return [
            {
                key: nextKey(),
                title: text(node.title),
                page: text(node.page),
                label: text(node.label),
                pages: parseNodes(node.pages, nextKey),
            },
        ];
    });
}

function serializeNodes(nodes: EditorNode[]): unknown[] {
    return nodes.flatMap((node): unknown[] => {
        const pages = serializeNodes(node.pages);
        const title = node.title.trim();
        const page = node.page.trim();
        const label = node.label.trim();

        if (!title && !page && pages.length === 0) {
            return [];
        }

        if (page && !title && !label && pages.length === 0) {
            return [page];
        }

        return [
            {
                ...(title && { title }),
                ...(page && { page }),
                ...(label && { label }),
                ...(pages.length > 0 && { pages }),
            },
        ];
    });
}

function collectPages(nodes: EditorNode[]): string[] {
    return nodes.flatMap((node) => [
        ...(node.page ? [node.page] : []),
        ...collectPages(node.pages),
    ]);
}

function updateList(
    nodes: EditorNode[],
    parentPath: number[],
    change: (list: EditorNode[]) => EditorNode[],
): EditorNode[] {
    if (parentPath.length === 0) {
        return change(nodes);
    }

    const [index, ...rest] = parentPath;

    return nodes.map((node, i) =>
        i === index
            ? { ...node, pages: updateList(node.pages, rest, change) }
            : node,
    );
}

function moveItem(list: EditorNode[], from: number, to: number): EditorNode[] {
    if (to < 0 || to >= list.length) {
        return list;
    }

    const next = [...list];
    [next[from], next[to]] = [next[to], next[from]];

    return next;
}

function nodeTitle(
    node: EditorNode,
    documents: NavigationDocumentOption[],
): string {
    return (
        node.title ||
        documents.find((document) => document.path === node.page)?.title ||
        node.page ||
        '新しいグループ'
    );
}

function findNode(
    nodes: EditorNode[],
    key: number | null,
    parentPath: number[] = [],
): { node: EditorNode; path: number[]; siblings: number } | null {
    for (const [index, node] of nodes.entries()) {
        const path = [...parentPath, index];
        if (node.key === key) {
            return { node, path, siblings: nodes.length };
        }
        const child = findNode(node.pages, key, path);
        if (child) {
            return child;
        }
    }
    return null;
}

type DropTarget = {
    key: number | null;
    position: 'before' | 'inside' | 'after';
};

function nodeHeight(node: EditorNode): number {
    return 1 + Math.max(0, ...node.pages.map(nodeHeight));
}

function canMoveNode(
    nodes: EditorNode[],
    sourceKey: number,
    target: DropTarget,
): boolean {
    const source = findNode(nodes, sourceKey);
    if (!source) {
        return false;
    }
    if (target.key === null) {
        return nodeHeight(source.node) <= MAX_DEPTH;
    }
    const destination = findNode(nodes, target.key);
    if (
        !destination ||
        sourceKey === target.key ||
        findNode(source.node.pages, target.key)
    ) {
        return false;
    }
    const parentDepth =
        destination.path.length - (target.position === 'inside' ? 0 : 1);
    return parentDepth + nodeHeight(source.node) <= MAX_DEPTH;
}

function moveNode(
    nodes: EditorNode[],
    sourceKey: number,
    target: DropTarget,
): EditorNode[] {
    if (!canMoveNode(nodes, sourceKey, target)) {
        return nodes;
    }
    const source = findNode(nodes, sourceKey)!;
    const remaining = updateList(nodes, source.path.slice(0, -1), (list) =>
        list.filter((node) => node.key !== sourceKey),
    );
    if (target.key === null) {
        return [...remaining, source.node];
    }
    const destination = findNode(remaining, target.key)!;
    if (target.position === 'inside') {
        return updateList(remaining, destination.path, (list) => [
            ...list,
            source.node,
        ]);
    }
    const index =
        destination.path.at(-1)! + (target.position === 'after' ? 1 : 0);
    return updateList(remaining, destination.path.slice(0, -1), (list) => [
        ...list.slice(0, index),
        source.node,
        ...list.slice(index),
    ]);
}

type NavigationDrag = {
    sourceKey: number | null;
    target: DropTarget | null;
    start: (event: DragEvent, key: number) => void;
    over: (event: DragEvent, target: DropTarget) => void;
    drop: (event: DragEvent, target: DropTarget) => void;
    end: () => void;
};

function rowDropTarget(event: DragEvent<HTMLElement>, key: number): DropTarget {
    const rect = event.currentTarget.getBoundingClientRect();
    const fraction = (event.clientY - rect.top) / rect.height;
    return {
        key,
        position:
            fraction < 0.25 ? 'before' : fraction > 0.75 ? 'after' : 'inside',
    };
}

function NodeList({
    nodes,
    documents,
    selectedKey,
    onSelect,
    drag,
}: {
    nodes: EditorNode[];
    documents: NavigationDocumentOption[];
    selectedKey: number | null;
    onSelect: (key: number) => void;
    drag: NavigationDrag;
}) {
    return (
        <ul className="min-w-0 space-y-1">
            {nodes.map((node) => (
                <li key={node.key} className="min-w-0">
                    <div
                        onDragOver={(event) =>
                            drag.over(event, rowDropTarget(event, node.key))
                        }
                        onDrop={(event) =>
                            drag.drop(event, rowDropTarget(event, node.key))
                        }
                        className={cn(
                            'relative flex min-w-0 items-center rounded-md',
                            drag.sourceKey === node.key && 'opacity-40',
                            drag.target?.key === node.key &&
                                drag.target.position === 'inside' &&
                                'bg-primary/10 ring-2 ring-primary',
                            drag.target?.key === node.key &&
                                drag.target.position === 'before' &&
                                'before:absolute before:inset-x-0 before:top-0 before:border-t-2 before:border-primary',
                            drag.target?.key === node.key &&
                                drag.target.position === 'after' &&
                                'after:absolute after:inset-x-0 after:bottom-0 after:border-b-2 after:border-primary',
                        )}
                    >
                        <button
                            type="button"
                            draggable
                            aria-label={`${nodeTitle(node, documents)}をドラッグして移動`}
                            title="ドラッグして移動"
                            onClick={() => onSelect(node.key)}
                            onDragStart={(event) => drag.start(event, node.key)}
                            onDragEnd={drag.end}
                            className="shrink-0 cursor-grab rounded p-2 text-muted-foreground hover:bg-accent focus-visible:outline-2 focus-visible:outline-ring active:cursor-grabbing"
                        >
                            <GripVertical className="size-4" />
                        </button>
                        <button
                            type="button"
                            onClick={() => onSelect(node.key)}
                            aria-pressed={selectedKey === node.key}
                            className={cn(
                                'flex w-full min-w-0 items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-accent focus-visible:outline-2 focus-visible:outline-ring',
                                selectedKey === node.key &&
                                    'bg-accent font-medium text-accent-foreground',
                            )}
                        >
                            {node.pages.length > 0 || !node.page ? (
                                <Folder className="size-4 shrink-0 text-muted-foreground" />
                            ) : (
                                <FileText className="size-4 shrink-0 text-muted-foreground" />
                            )}
                            <span className="min-w-0 flex-1 break-words">
                                {nodeTitle(node, documents)}
                            </span>
                            {node.label && (
                                <span className="max-w-20 truncate rounded border px-1 text-xs text-muted-foreground">
                                    {node.label}
                                </span>
                            )}
                        </button>
                    </div>
                    {node.pages.length > 0 && (
                        <details open className="ml-3 border-l pl-2">
                            <summary className="cursor-pointer px-2 py-1 text-xs text-muted-foreground hover:text-foreground">
                                下位 {node.pages.length} 項目
                            </summary>
                            <NodeList
                                nodes={node.pages}
                                documents={documents}
                                selectedKey={selectedKey}
                                onSelect={onSelect}
                                drag={drag}
                            />
                        </details>
                    )}
                </li>
            ))}
        </ul>
    );
}

function NodeEditor({
    node,
    documents,
    onPatch,
}: {
    node: EditorNode;
    documents: NavigationDocumentOption[];
    onPatch: (values: Partial<EditorNode>) => void;
}) {
    const [query, setQuery] = useState('');
    const [choosing, setChoosing] = useState(false);
    const matches = documents.filter((document) =>
        (document.title + ' ' + document.path)
            .toLocaleLowerCase()
            .includes(query.toLocaleLowerCase().trim()),
    );

    return (
        <div className="grid min-w-0 gap-4">
            <div className="grid gap-2">
                <Label htmlFor="navigation-title">表示名</Label>
                <Input
                    id="navigation-title"
                    value={node.title}
                    onChange={(event) => onPatch({ title: event.target.value })}
                    placeholder="空欄なら記事タイトルを使用"
                />
            </div>
            <div className="grid min-w-0 gap-2">
                <Label>リンク先の記事</Label>
                <div className="rounded-md border p-3 text-sm">
                    <p className="break-words">
                        {node.page
                            ? documents.find(
                                  (document) => document.path === node.page,
                              )?.title || '未登録の記事'
                            : 'リンクなし（グループ見出し）'}
                    </p>
                    {node.page && (
                        <p className="mt-1 text-xs break-all text-muted-foreground">
                            {node.page}
                        </p>
                    )}
                    <div className="mt-2 flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            aria-expanded={choosing}
                            onClick={() => setChoosing(!choosing)}
                        >
                            {choosing ? '閉じる' : '記事を選ぶ'}
                        </Button>
                        {node.page && (
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={() => onPatch({ page: '' })}
                            >
                                リンクを外す
                            </Button>
                        )}
                    </div>
                </div>
                {choosing && (
                    <div className="grid gap-2 rounded-md border p-2">
                        <Input
                            aria-label="リンク先の記事を検索"
                            placeholder="タイトル・パスで検索"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                        />
                        <div className="max-h-60 overflow-y-auto">
                            {matches.map((document) => (
                                <button
                                    key={document.path}
                                    type="button"
                                    aria-pressed={node.page === document.path}
                                    className="block w-full rounded px-2 py-2 text-left text-sm hover:bg-accent focus-visible:outline-2 focus-visible:outline-ring"
                                    onClick={() => {
                                        onPatch({ page: document.path });
                                        setChoosing(false);
                                    }}
                                >
                                    <span className="block break-words">
                                        {document.title}
                                    </span>
                                    <span className="block text-xs break-all text-muted-foreground">
                                        {document.path}
                                    </span>
                                </button>
                            ))}
                            {matches.length === 0 && (
                                <p className="p-2 text-sm text-muted-foreground">
                                    該当する記事はありません。
                                </p>
                            )}
                        </div>
                    </div>
                )}
            </div>
            <div className="grid gap-2">
                <Label htmlFor="navigation-label">ラベル（任意）</Label>
                <Input
                    id="navigation-label"
                    value={node.label}
                    onChange={(event) => onPatch({ label: event.target.value })}
                    placeholder="例：初級"
                />
            </div>
        </div>
    );
}

/**
 * Structured editor for a namespace's navigation tree. The tree is submitted
 * as JSON through a hidden `navigation` field; a raw JSON mode is available
 * for edits the form can't express.
 */
export function NavigationEditor({
    defaultValue,
    documents,
    error,
}: NavigationEditorProps) {
    const keyCounter = useRef(0);
    const nextKey = () => ++keyCounter.current;

    const [nodes, setNodes] = useState<EditorNode[]>(() => {
        try {
            return defaultValue
                ? parseNodes(JSON.parse(defaultValue), nextKey)
                : [];
        } catch {
            return [];
        }
    });
    const [mode, setMode] = useState<'visual' | 'json'>('visual');
    const [text, setText] = useState(defaultValue);
    const [switchError, setSwitchError] = useState<string | null>(null);
    const [selectedKey, setSelectedKey] = useState<number | null>(null);
    const [documentQuery, setDocumentQuery] = useState('');
    const selected = findNode(nodes, selectedKey);
    const [draggedKey, setDraggedKey] = useState<number | null>(null);
    const draggedKeyRef = useRef<number | null>(null);
    const [dropTarget, setDropTarget] = useState<DropTarget | null>(null);
    const [moveMessage, setMoveMessage] = useState('');

    const endDrag = () => {
        draggedKeyRef.current = null;
        setDraggedKey(null);
        setDropTarget(null);
    };
    const drag: NavigationDrag = {
        sourceKey: draggedKey,
        target: dropTarget,
        start: (event, key) => {
            event.stopPropagation();
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(key));
            draggedKeyRef.current = key;
            setDraggedKey(key);
            setMoveMessage('行の上下端で並べ替え、中央で下位に移動できます。');
        },
        over: (event, target) => {
            event.stopPropagation();
            const key = draggedKeyRef.current;
            if (key === null) {
                return;
            }
            event.preventDefault();
            if (!canMoveNode(nodes, key, target)) {
                event.dataTransfer.dropEffect = 'none';
                setDropTarget(null);
                setMoveMessage(
                    'ここには移動できません。自分自身・下位の項目・階層上限を確認してください。',
                );
                return;
            }
            event.dataTransfer.dropEffect = 'move';
            setDropTarget(target);
            const destination = findNode(nodes, target.key);
            const title = destination
                ? nodeTitle(destination.node, documents)
                : '';
            setMoveMessage(
                target.key === null
                    ? '最上位の末尾へ移動'
                    : `「${title}」の${target.position === 'inside' ? '下位' : target.position === 'before' ? '前' : '後'}へ移動`,
            );
        },
        drop: (event, target) => {
            event.stopPropagation();
            const key = draggedKeyRef.current;
            if (key !== null) {
                event.preventDefault();
                if (canMoveNode(nodes, key, target)) {
                    setNodes((current) => moveNode(current, key, target));
                    setSelectedKey(key);
                    setMoveMessage(
                        '移動しました。「更新」で保存してください。',
                    );
                }
            }
            endDrag();
        },
        end: endDrag,
    };

    const createNode = (page = ''): EditorNode => ({
        key: nextKey(),
        title: '',
        page,
        label: '',
        pages: [],
    });

    const change = (
        parentPath: number[],
        apply: (list: EditorNode[]) => EditorNode[],
    ) => setNodes((current) => updateList(current, parentPath, apply));

    const addNode = (parentPath: number[], page = '', select = true) => {
        const node = createNode(page);
        change(parentPath, (list) => [...list, node]);
        if (select) {
            setSelectedKey(node.key);
        }
    };

    const serialized = serializeNodes(nodes);
    const submitted =
        mode === 'json'
            ? text
            : serialized.length > 0
              ? JSON.stringify(serialized)
              : '';

    const switchMode = (next: 'visual' | 'json') => {
        if (next === mode) {
            return;
        }

        setSwitchError(null);

        if (next === 'json') {
            setText(
                serialized.length > 0
                    ? JSON.stringify(serialized, null, 2)
                    : '',
            );
            setMode('json');

            return;
        }

        try {
            const parsed: unknown = text.trim() ? JSON.parse(text) : [];

            if (!Array.isArray(parsed)) {
                throw new Error('not an array');
            }

            setNodes(parseNodes(parsed, nextKey));
            setSelectedKey(null);
            setMode('visual');
        } catch {
            setSwitchError(
                'JSONが正しくないため、フォーム表示に切り替えられません。',
            );
        }
    };

    const listedPages = new Set(collectPages(nodes));
    const unlistedDocuments = documents.filter(
        (document) => !listedPages.has(document.path),
    );
    const matchingDocuments = unlistedDocuments.filter((document) =>
        (document.title + ' ' + document.path)
            .toLocaleLowerCase()
            .includes(documentQuery.toLocaleLowerCase().trim()),
    );

    return (
        <div className="grid min-w-0 gap-3">
            <input type="hidden" name="navigation" value={submitted} />

            <div className="flex gap-1">
                <Button
                    type="button"
                    size="sm"
                    variant={mode === 'visual' ? 'secondary' : 'ghost'}
                    onClick={() => switchMode('visual')}
                >
                    メニューを編集
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant={mode === 'json' ? 'secondary' : 'ghost'}
                    onClick={() => switchMode('json')}
                >
                    JSON編集
                </Button>
            </div>

            {mode === 'json' ? (
                <Textarea
                    value={text}
                    onChange={(e) => setText(e.target.value)}
                    rows={16}
                    spellCheck={false}
                    className="font-mono text-xs"
                    aria-label="ナビゲーションJSON"
                    aria-invalid={Boolean(error)}
                />
            ) : (
                <>
                    <p className="text-sm text-muted-foreground">
                        一覧から項目を選んで編集します。変更は画面下の「更新」で保存されます。
                        ハンドルをドラッグし、行の上下端で並べ替え、中央で下位に移動できます。
                    </p>
                    <p
                        role="status"
                        className="min-h-5 text-sm text-muted-foreground"
                    >
                        {moveMessage}
                    </p>
                    <div className="grid min-w-0 overflow-hidden rounded-lg border lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                        <div className="min-w-0 border-b bg-muted/20 lg:border-r lg:border-b-0">
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b p-3">
                                <h3 className="text-sm font-medium">
                                    メニュー構成
                                </h3>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => addNode([])}
                                >
                                    <Plus /> 項目を追加
                                </Button>
                            </div>
                            <div className="max-h-96 overflow-y-auto p-2 lg:max-h-[36rem]">
                                <NodeList
                                    nodes={nodes}
                                    documents={documents}
                                    selectedKey={selectedKey}
                                    onSelect={setSelectedKey}
                                    drag={drag}
                                />
                                {draggedKey !== null && (
                                    <div
                                        onDragOver={(event) =>
                                            drag.over(event, {
                                                key: null,
                                                position: 'after',
                                            })
                                        }
                                        onDrop={(event) =>
                                            drag.drop(event, {
                                                key: null,
                                                position: 'after',
                                            })
                                        }
                                        className={cn(
                                            'mt-2 rounded-md border-2 border-dashed p-3 text-center text-sm text-muted-foreground',
                                            dropTarget?.key === null &&
                                                'border-primary bg-primary/10',
                                        )}
                                    >
                                        最上位の末尾へ移動
                                    </div>
                                )}
                                {nodes.length === 0 && (
                                    <p className="p-4 text-sm text-muted-foreground">
                                        まだ項目がありません。項目を追加するか、下の記事一覧から選んでください。
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="min-w-0 p-4">
                            {selected ? (
                                <div className="grid gap-4">
                                    <div>
                                        <h3 className="text-sm font-medium">
                                            項目の編集
                                        </h3>
                                        <p className="mt-1 text-xs break-words text-muted-foreground">
                                            {nodeTitle(
                                                selected.node,
                                                documents,
                                            )}
                                        </p>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setSelectedKey(null)}
                                        >
                                            選択を解除
                                        </Button>
                                    </div>
                                    <NodeEditor
                                        key={selected.node.key}
                                        node={selected.node}
                                        documents={documents}
                                        onPatch={(values) =>
                                            change(
                                                selected.path.slice(0, -1),
                                                (list) =>
                                                    list.map((node) =>
                                                        node.key ===
                                                        selected.node.key
                                                            ? {
                                                                  ...node,
                                                                  ...values,
                                                              }
                                                            : node,
                                                    ),
                                            )
                                        }
                                    />
                                    <div className="flex flex-wrap gap-2 border-t pt-4">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={
                                                selected.path.at(-1) === 0
                                            }
                                            onClick={() =>
                                                change(
                                                    selected.path.slice(0, -1),
                                                    (list) =>
                                                        moveItem(
                                                            list,
                                                            selected.path.at(
                                                                -1,
                                                            )!,
                                                            selected.path.at(
                                                                -1,
                                                            )! - 1,
                                                        ),
                                                )
                                            }
                                        >
                                            <ArrowUp /> 上へ
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={
                                                selected.path.at(-1) ===
                                                selected.siblings - 1
                                            }
                                            onClick={() =>
                                                change(
                                                    selected.path.slice(0, -1),
                                                    (list) =>
                                                        moveItem(
                                                            list,
                                                            selected.path.at(
                                                                -1,
                                                            )!,
                                                            selected.path.at(
                                                                -1,
                                                            )! + 1,
                                                        ),
                                                )
                                            }
                                        >
                                            <ArrowDown /> 下へ
                                        </Button>
                                        {selected.path.length < MAX_DEPTH && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    addNode(selected.path)
                                                }
                                            >
                                                <CornerDownRight /> 下位に追加
                                            </Button>
                                        )}
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="justify-self-start text-destructive hover:text-destructive"
                                        onClick={() => {
                                            if (
                                                selected.node.pages.length >
                                                    0 &&
                                                !window.confirm(
                                                    'この項目と、含まれる下位の項目をメニューから削除しますか？ 記事自体は削除されません。',
                                                )
                                            ) {
                                                return;
                                            }
                                            change(
                                                selected.path.slice(0, -1),
                                                (list) =>
                                                    list.filter(
                                                        (node) =>
                                                            node.key !==
                                                            selected.node.key,
                                                    ),
                                            );
                                            setSelectedKey(null);
                                        }}
                                    >
                                        <Trash2 /> メニューから削除
                                    </Button>
                                </div>
                            ) : (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    編集する項目を一覧から選んでください。
                                </p>
                            )}
                        </div>
                    </div>

                    {unlistedDocuments.length > 0 && (
                        <details className="rounded-md border">
                            <summary className="cursor-pointer p-3 text-sm font-medium">
                                メニューに追加できる記事（
                                {unlistedDocuments.length}件）
                            </summary>
                            <div className="grid gap-3 border-t p-3">
                                <p className="text-xs text-muted-foreground">
                                    {selected
                                        ? selected.path.length < MAX_DEPTH
                                            ? `「${nodeTitle(selected.node, documents)}」の下位に追加します。`
                                            : '階層の上限です。追加先のグループを選び直してください。'
                                        : 'メニューの末尾に追加します。'}{' '}
                                    未追加の記事はサイドバーに表示されません。記事一覧からは開けます。
                                </p>
                                <Input
                                    aria-label="追加する記事を検索"
                                    placeholder="タイトル・パスで検索"
                                    value={documentQuery}
                                    onChange={(event) =>
                                        setDocumentQuery(event.target.value)
                                    }
                                />
                                <div className="max-h-64 divide-y overflow-y-auto">
                                    {matchingDocuments.map((document) => (
                                        <button
                                            key={document.path}
                                            type="button"
                                            disabled={Boolean(
                                                selected &&
                                                selected.path.length >=
                                                    MAX_DEPTH,
                                            )}
                                            className="flex w-full items-center gap-3 rounded px-2 py-3 text-left text-sm hover:bg-accent focus-visible:outline-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50"
                                            onClick={() =>
                                                addNode(
                                                    selected?.path ?? [],
                                                    document.path,
                                                    false,
                                                )
                                            }
                                        >
                                            <Plus className="size-4 shrink-0" />
                                            <span className="min-w-0">
                                                <span className="block break-words">
                                                    {document.title}
                                                </span>
                                                <span className="block text-xs break-all text-muted-foreground">
                                                    {document.path}
                                                </span>
                                            </span>
                                        </button>
                                    ))}
                                    {matchingDocuments.length === 0 && (
                                        <p className="p-2 text-sm text-muted-foreground">
                                            該当する記事はありません。
                                        </p>
                                    )}
                                </div>
                            </div>
                        </details>
                    )}
                </>
            )}

            {switchError && (
                <p className="text-sm text-destructive">{switchError}</p>
            )}
        </div>
    );
}
