import type { ReactNode } from 'react';
import Markdown from 'react-markdown';
import type { Components } from 'react-markdown';
import remarkDirective from 'remark-directive';
import remarkGfm from 'remark-gfm';
import { cn } from '@/lib/utils';
import {
    normalizeMintlifyBlocks,
    normalizeZennDirectiveShorthand,
    remarkCodeFenceComponents,
    remarkMintlifyTags,
    remarkZennDirective,
} from '@catatsumuri/inkstream2';
import type {
    ChartConfig,
    QuizContent,
    TreeNode,
} from '@catatsumuri/inkstream2';

type MarkdownContentProps = {
    children: string;
};

type MintlifyElementProps = {
    children?: ReactNode;
    className?: string;
    title?: string;
    href?: string;
    color?: string;
    tip?: string;
    tree?: string;
    quiz?: string;
    chart?: string;
};

const CALLOUT_VARIANT_CLASSES: Record<string, string> = {
    note: 'border-blue-500/50 bg-blue-500/10',
    tip: 'border-emerald-500/50 bg-emerald-500/10',
    info: 'border-sky-500/50 bg-sky-500/10',
    alert: 'border-red-500/50 bg-red-500/10',
    check: 'border-green-500/50 bg-green-500/10',
};

const BADGE_COLOR_CLASSES: Record<string, string> = {
    green: 'bg-emerald-500/10 text-emerald-600',
    red: 'bg-red-500/10 text-red-600',
    blue: 'bg-sky-500/10 text-sky-600',
    yellow: 'bg-amber-500/10 text-amber-600',
};

function parseJsonProp<T>(value: string | undefined): T | null {
    if (!value) {
        return null;
    }

    try {
        return JSON.parse(value) as T;
    } catch {
        return null;
    }
}

function TreeNodeItem({ node }: { node: TreeNode }) {
    if (node.type === 'file') {
        return <li className="pl-4">📄 {node.name}</li>;
    }

    return (
        <li>
            <details open={node.defaultOpen}>
                <summary className="cursor-pointer">📁 {node.name}</summary>
                <ul className="ml-4 border-l border-border pl-2">
                    {(node.children ?? []).map((child, index) => (
                        <TreeNodeItem key={index} node={child} />
                    ))}
                </ul>
            </details>
        </li>
    );
}

/**
 * Renderers for the custom elements emitted by inkstream2's
 * remarkMintlifyTags (aside, card, steps, ...) and remarkCodeFenceComponents
 * (tree, quiz, chart). The tag names are not part of react-markdown's
 * Components type, hence the cast at the end.
 */
const mintlifyComponents = {
    aside: ({ className, children }: MintlifyElementProps) => {
        const variant =
            className?.split(' ').find((token) => token !== 'msg') ?? 'info';

        return (
            <aside
                className={cn(
                    'grid gap-2 rounded-lg border-l-4 p-4',
                    CALLOUT_VARIANT_CLASSES[variant] ??
                        CALLOUT_VARIANT_CLASSES.info,
                )}
            >
                {children}
            </aside>
        );
    },
    card: ({ title, href, children }: MintlifyElementProps) => {
        const body = (
            <div className="grid h-full gap-2 rounded-lg border border-border p-4">
                {title && <p className="font-semibold">{title}</p>}
                {children}
            </div>
        );

        return href ? (
            <a href={href} className="block !no-underline">
                {body}
            </a>
        ) : (
            body
        );
    },
    cardgroup: ({ children }: MintlifyElementProps) => (
        <div className="grid gap-4 md:grid-cols-2">{children}</div>
    ),
    steps: ({ children }: MintlifyElementProps) => (
        <ol className="grid gap-3">{children}</ol>
    ),
    step: ({ title, children }: MintlifyElementProps) => (
        <li className="ml-5 grid gap-1">
            {title && <p className="font-semibold">{title}</p>}
            {children}
        </li>
    ),
    tabs: ({ children }: MintlifyElementProps) => (
        <div className="grid gap-2">{children}</div>
    ),
    tab: ({ title, children }: MintlifyElementProps) => (
        <section className="grid gap-2 rounded-lg border border-border p-4">
            {title && <p className="font-semibold">{title}</p>}
            {children}
        </section>
    ),
    accordiongroup: ({ children }: MintlifyElementProps) => (
        <div className="grid gap-2">{children}</div>
    ),
    accordion: ({ title, children }: MintlifyElementProps) => (
        <details className="rounded-lg border border-border p-3">
            <summary className="cursor-pointer font-medium">
                {title ?? 'Details'}
            </summary>
            <div className="mt-2 grid gap-2">{children}</div>
        </details>
    ),
    badge: ({ color, children }: MintlifyElementProps) => (
        <span
            className={cn(
                'rounded-full px-2 py-0.5 text-xs font-medium',
                BADGE_COLOR_CLASSES[color ?? ''] ??
                    'bg-muted text-muted-foreground',
            )}
        >
            {children}
        </span>
    ),
    tooltip: ({ tip, children }: MintlifyElementProps) => (
        <span className="underline decoration-dotted" title={tip}>
            {children}
        </span>
    ),
    tree: ({ tree }: MintlifyElementProps) => {
        const nodes = parseJsonProp<TreeNode[]>(tree);

        if (!nodes) {
            return null;
        }

        return (
            <ul className="grid gap-0.5 rounded-lg border border-border p-3 font-mono text-xs">
                {nodes.map((node, index) => (
                    <TreeNodeItem key={index} node={node} />
                ))}
            </ul>
        );
    },
    quiz: ({ quiz }: MintlifyElementProps) => {
        const content = parseJsonProp<QuizContent>(quiz);

        if (!content) {
            return null;
        }

        return (
            <div className="grid gap-2 rounded-lg border border-border p-4">
                <p className="font-semibold">{content.question}</p>
                <ul className="grid gap-1">
                    {content.options.map((option) => (
                        <li
                            key={option.label}
                            className={cn(
                                'rounded border px-2 py-1',
                                option.label === content.correct
                                    ? 'border-emerald-500/50 bg-emerald-500/10'
                                    : 'border-border',
                            )}
                        >
                            <span className="font-medium">{option.label}.</span>{' '}
                            {option.text}
                        </li>
                    ))}
                </ul>
                {content.explanation && (
                    <p className="text-xs text-muted-foreground">
                        {content.explanation}
                    </p>
                )}
            </div>
        );
    },
    chart: ({ chart }: MintlifyElementProps) => {
        const config = parseJsonProp<ChartConfig>(chart);

        if (!config) {
            return null;
        }

        const min = config.min ?? 0;
        const max =
            config.max ?? Math.max(...config.data.map((point) => point.value));
        const domain = Math.max(max - min, 1);

        return (
            <div className="grid gap-2 rounded-lg border border-border p-4">
                {config.title && (
                    <p className="font-semibold">{config.title}</p>
                )}
                <div className="grid gap-1.5">
                    {config.data.map((point) => (
                        <div
                            key={point.label}
                            className="grid grid-cols-[5rem_1fr_3rem] items-center gap-2 text-xs"
                        >
                            <span className="truncate">{point.label}</span>
                            <div className="h-2 rounded bg-muted">
                                <div
                                    className="h-2 rounded bg-primary"
                                    style={{
                                        width: `${((point.value - min) / domain) * 100}%`,
                                    }}
                                />
                            </div>
                            <span className="text-right tabular-nums">
                                {point.value}
                            </span>
                        </div>
                    ))}
                </div>
            </div>
        );
    },
} as unknown as Components;

export function MarkdownContent({ children }: MarkdownContentProps) {
    return (
        <div className="space-y-4 text-sm leading-7 break-words [&_a]:font-medium [&_a]:text-primary [&_a]:underline [&_a]:underline-offset-4 [&_blockquote]:border-l-4 [&_blockquote]:border-border [&_blockquote]:pl-4 [&_blockquote]:text-muted-foreground [&_code]:rounded [&_code]:bg-muted [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-sm [&_details]:rounded-lg [&_details]:border [&_details]:border-border [&_details]:p-3 [&_h1]:text-2xl [&_h1]:font-bold [&_h2]:text-xl [&_h2]:font-semibold [&_h3]:text-lg [&_h3]:font-semibold [&_hr]:border-border [&_li]:ml-5 [&_ol]:list-decimal [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-muted [&_pre]:p-4 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_summary]:cursor-pointer [&_summary]:font-medium [&_table]:w-full [&_table]:border-collapse [&_td]:border [&_td]:border-border [&_td]:p-2 [&_th]:border [&_th]:border-border [&_th]:bg-muted [&_th]:p-2 [&_th]:text-left [&_ul]:list-disc">
            <Markdown
                remarkPlugins={[
                    remarkGfm,
                    remarkDirective,
                    remarkZennDirective,
                    remarkMintlifyTags,
                    remarkCodeFenceComponents,
                ]}
                components={mintlifyComponents}
            >
                {normalizeZennDirectiveShorthand(
                    normalizeMintlifyBlocks(children),
                )}
            </Markdown>
        </div>
    );
}
