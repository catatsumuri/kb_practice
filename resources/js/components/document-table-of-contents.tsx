import { lang } from '@erag/lang-sync-inertia/react';
import type { RefObject } from 'react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

type TocHeading = {
    id: string;
    text: string;
    level: number;
};

// Distance below the top of the visible reading area at which a heading
// counts as 'reached'.
const ACTIVE_HEADING_OFFSET = 24;
const WINDOW_ACTIVE_HEADING_OFFSET = 96;

/**
 * Table of contents that highlights the heading currently being read.
 * The document body scrolls inside `contentPaneRef` in reader mode and
 * with the window otherwise, so both are listened to (scroll events don't
 * bubble, hence the capturing listener on the document).
 */
export function DocumentTableOfContents({
    headings,
    contentPaneRef,
    scrollContainerRef,
}: {
    headings: TocHeading[];
    contentPaneRef: RefObject<HTMLElement | null>;
    scrollContainerRef: RefObject<HTMLElement | null>;
}) {
    const { __ } = lang();
    const [activeId, setActiveId] = useState<string | null>(
        headings[0]?.id ?? null,
    );
    const itemRefs = useRef<Map<string, HTMLAnchorElement>>(new Map());

    useEffect(() => {
        if (headings.length === 0) {
            return;
        }

        const updateActiveId = () => {
            const pane = contentPaneRef.current;
            const paneScrolls =
                pane !== null && getComputedStyle(pane).overflowY !== 'visible';
            const threshold = paneScrolls
                ? pane.getBoundingClientRect().top + ACTIVE_HEADING_OFFSET
                : WINDOW_ACTIVE_HEADING_OFFSET;

            let nextActiveId = headings[0].id;

            for (const heading of headings) {
                const element = document.getElementById(heading.id);

                if (!element) {
                    continue;
                }

                if (element.getBoundingClientRect().top <= threshold) {
                    nextActiveId = heading.id;
                } else {
                    break;
                }
            }

            setActiveId(nextActiveId);
        };

        updateActiveId();

        document.addEventListener('scroll', updateActiveId, {
            capture: true,
            passive: true,
        });
        window.addEventListener('resize', updateActiveId);
        window.addEventListener('hashchange', updateActiveId);

        return () => {
            document.removeEventListener('scroll', updateActiveId, {
                capture: true,
            });
            window.removeEventListener('resize', updateActiveId);
            window.removeEventListener('hashchange', updateActiveId);
        };
    }, [headings, contentPaneRef]);

    // Keep the active entry visible when the contents list itself scrolls.
    useEffect(() => {
        const container = scrollContainerRef.current;
        const item = activeId ? itemRefs.current.get(activeId) : undefined;

        if (
            !container ||
            !item ||
            container.scrollHeight <= container.clientHeight
        ) {
            return;
        }

        const containerRect = container.getBoundingClientRect();
        const itemRect = item.getBoundingClientRect();

        if (itemRect.top < containerRect.top) {
            container.scrollTop -= containerRect.top - itemRect.top + 8;
        } else if (itemRect.bottom > containerRect.bottom) {
            container.scrollTop += itemRect.bottom - containerRect.bottom + 8;
        }
    }, [activeId, scrollContainerRef]);

    return (
        <nav className="rounded-md border p-4 text-sm">
            <p className="mb-2 font-semibold text-foreground">
                {__('Contents')}
            </p>
            <ul className="space-y-1">
                {headings.map((heading) => (
                    <li
                        key={heading.id}
                        style={{ paddingLeft: `${(heading.level - 1) * 12}px` }}
                    >
                        <a
                            ref={(element) => {
                                if (element) {
                                    itemRefs.current.set(heading.id, element);
                                } else {
                                    itemRefs.current.delete(heading.id);
                                }
                            }}
                            href={`#${encodeURIComponent(heading.id)}`}
                            onClick={(event) => {
                                event.preventDefault();
                                document
                                    .getElementById(heading.id)
                                    ?.scrollIntoView({ block: 'start' });
                                setActiveId(heading.id);
                            }}
                            data-active={activeId === heading.id}
                            aria-current={
                                activeId === heading.id ? 'location' : undefined
                            }
                            className={cn(
                                'block rounded px-1 py-0.5 transition-colors',
                                activeId === heading.id
                                    ? 'bg-accent font-medium text-foreground'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {heading.text}
                        </a>
                    </li>
                ))}
            </ul>
        </nav>
    );
}
