import type { MarkdownHeading } from '@catatsumuri/inkstream';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

const ACTIVE_HEADING_OFFSET = 160;

type DocumentTableOfContentsProps = {
    headings: MarkdownHeading[];
};

/**
 * Sticky scrollspy nav built from inkstream's extractMarkdownHeadings.
 * Ids come from the same slugify pass the rendered headings use, so links
 * here always resolve to a real anchor in the document.
 */
export function DocumentTableOfContents({
    headings,
}: DocumentTableOfContentsProps) {
    const itemRefs = useRef<Map<string, HTMLAnchorElement>>(new Map());
    const [activeId, setActiveId] = useState<string | null>(
        headings[0]?.id ?? null,
    );

    useEffect(() => {
        if (headings.length === 0) {
            return;
        }

        const updateActiveId = () => {
            let nextActiveId = headings[0]?.id ?? null;

            for (const heading of headings) {
                const element = document.getElementById(heading.id);

                if (!element) {
                    continue;
                }

                if (
                    element.getBoundingClientRect().top <= ACTIVE_HEADING_OFFSET
                ) {
                    nextActiveId = heading.id;
                } else {
                    break;
                }
            }

            setActiveId((currentActiveId) =>
                currentActiveId === nextActiveId
                    ? currentActiveId
                    : nextActiveId,
            );
        };

        updateActiveId();

        window.addEventListener('scroll', updateActiveId, { passive: true });
        window.addEventListener('resize', updateActiveId);
        window.addEventListener('hashchange', updateActiveId);

        return () => {
            window.removeEventListener('scroll', updateActiveId);
            window.removeEventListener('resize', updateActiveId);
            window.removeEventListener('hashchange', updateActiveId);
        };
    }, [headings]);

    const registerItemRef =
        (id: string) => (element: HTMLAnchorElement | null) => {
            if (element) {
                itemRefs.current.set(id, element);
            } else {
                itemRefs.current.delete(id);
            }
        };

    const handleClick =
        (id: string) => (event: React.MouseEvent<HTMLAnchorElement>) => {
            event.preventDefault();
            document.getElementById(id)?.scrollIntoView({ block: 'start' });
            window.history.pushState(null, '', `#${encodeURIComponent(id)}`);
            setActiveId(id);
        };

    if (headings.length === 0) {
        return null;
    }

    return (
        <nav
            data-test="document-table-of-contents"
            className="sticky top-24 max-h-[calc(100vh-7rem)] space-y-1 overflow-y-auto pr-1 text-sm"
        >
            <p className="px-2 pb-1 font-semibold text-foreground">目次</p>
            {headings.map((heading) => (
                <a
                    key={heading.id}
                    ref={registerItemRef(heading.id)}
                    href={`#${encodeURIComponent(heading.id)}`}
                    onClick={handleClick(heading.id)}
                    data-test={`toc-link-${heading.id}`}
                    aria-current={
                        activeId === heading.id ? 'location' : undefined
                    }
                    style={{
                        paddingLeft: `${(heading.level - 1) * 12 + 8}px`,
                    }}
                    className={cn(
                        'block rounded-md px-2 py-1 leading-snug transition-colors',
                        activeId === heading.id
                            ? 'bg-accent font-medium text-foreground'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {heading.text}
                </a>
            ))}
        </nav>
    );
}
