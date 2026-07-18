import type { ReactNode } from 'react';
import Markdown from 'react-markdown';
import type { Components } from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { cn } from '@/lib/utils';
import {
    normalizeMintlifyBlocks,
    remarkMintlifyTags,
} from '@catatsumuri/inkstream2';

type MarkdownContentProps = {
    children: string;
};

type MintlifyElementProps = {
    children?: ReactNode;
    className?: string;
    title?: string;
    href?: string;
};

const CALLOUT_VARIANT_CLASSES: Record<string, string> = {
    note: 'border-blue-500/50 bg-blue-500/10',
    tip: 'border-emerald-500/50 bg-emerald-500/10',
    info: 'border-sky-500/50 bg-sky-500/10',
    alert: 'border-red-500/50 bg-red-500/10',
    check: 'border-green-500/50 bg-green-500/10',
};

/**
 * Renderers for the custom elements emitted by inkstream2's
 * remarkMintlifyTags (aside, card, steps, ...). The tag names are not part
 * of react-markdown's Components type, hence the cast at the end.
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
} as unknown as Components;

export function MarkdownContent({ children }: MarkdownContentProps) {
    return (
        <div className="space-y-4 text-sm leading-7 break-words [&_a]:font-medium [&_a]:text-primary [&_a]:underline [&_a]:underline-offset-4 [&_blockquote]:border-l-4 [&_blockquote]:border-border [&_blockquote]:pl-4 [&_blockquote]:text-muted-foreground [&_code]:rounded [&_code]:bg-muted [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-sm [&_h1]:text-2xl [&_h1]:font-bold [&_h2]:text-xl [&_h2]:font-semibold [&_h3]:text-lg [&_h3]:font-semibold [&_hr]:border-border [&_li]:ml-5 [&_ol]:list-decimal [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-muted [&_pre]:p-4 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_table]:w-full [&_table]:border-collapse [&_td]:border [&_td]:border-border [&_td]:p-2 [&_th]:border [&_th]:border-border [&_th]:bg-muted [&_th]:p-2 [&_th]:text-left [&_ul]:list-disc">
            <Markdown
                remarkPlugins={[remarkGfm, remarkMintlifyTags]}
                components={mintlifyComponents}
            >
                {normalizeMintlifyBlocks(children)}
            </Markdown>
        </div>
    );
}
