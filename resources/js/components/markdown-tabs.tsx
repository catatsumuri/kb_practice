import {
    Children,
    cloneElement,
    isValidElement,
    useId,
    useRef,
    useState,
} from 'react';
import type { Components } from 'react-markdown';
import type { KeyboardEvent, ReactElement, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type MarkdownTabProps = {
    title?: string;
    children?: ReactNode;
    hidden?: boolean;
    panelId?: string;
    labelledBy?: string;
};

/**
 * One panel of a Mintlify `<Tab title="...">`. The title is shown by the
 * surrounding MarkdownTabs, which also decides which panel is visible.
 */
function MarkdownTab({
    children,
    hidden,
    panelId,
    labelledBy,
}: MarkdownTabProps) {
    return (
        <div
            role="tabpanel"
            id={panelId}
            aria-labelledby={labelledBy}
            hidden={hidden}
            className="p-4"
        >
            {children}
        </div>
    );
}

/**
 * Renders Mintlify `<Tabs>` as a switchable tab bar. inkstream's default
 * renderer stacks every `<Tab>` as a card and has no state, so the app
 * supplies this one through InkstreamMarkdown's `components` prop.
 */
function MarkdownTabs({ children }: { children?: ReactNode }) {
    const tabs = Children.toArray(children).filter(
        isValidElement,
    ) as ReactElement<MarkdownTabProps>[];
    const [active, setActive] = useState(0);
    const baseId = useId();
    const buttons = useRef<(HTMLButtonElement | null)[]>([]);

    if (tabs.length === 0) {
        return <>{children}</>;
    }

    function select(index: number) {
        setActive(index);
        buttons.current[index]?.focus();
    }

    function handleKeyDown(event: KeyboardEvent<HTMLDivElement>) {
        const last = tabs.length - 1;
        const next = {
            ArrowRight: active === last ? 0 : active + 1,
            ArrowLeft: active === 0 ? last : active - 1,
            Home: 0,
            End: last,
        }[event.key];

        if (next !== undefined) {
            event.preventDefault();
            select(next);
        }
    }

    return (
        <div className="my-4 overflow-hidden rounded-lg border">
            <div
                role="tablist"
                onKeyDown={handleKeyDown}
                className="flex gap-1 overflow-x-auto border-b bg-muted/30 px-2"
            >
                {tabs.map((tab, index) => (
                    <button
                        key={index}
                        ref={(element) => {
                            buttons.current[index] = element;
                        }}
                        type="button"
                        role="tab"
                        id={`${baseId}-tab-${index}`}
                        aria-selected={index === active}
                        aria-controls={`${baseId}-panel-${index}`}
                        tabIndex={index === active ? 0 : -1}
                        onClick={() => setActive(index)}
                        className={cn(
                            '-mb-px border-b-2 border-transparent px-3 py-2 text-sm font-medium whitespace-nowrap text-muted-foreground transition-colors hover:text-foreground',
                            index === active &&
                                'border-primary text-foreground',
                        )}
                    >
                        {tab.props.title ?? `Tab ${index + 1}`}
                    </button>
                ))}
            </div>

            {tabs.map((tab, index) =>
                cloneElement(tab, {
                    key: index,
                    hidden: index !== active,
                    panelId: `${baseId}-panel-${index}`,
                    labelledBy: `${baseId}-tab-${index}`,
                }),
            )}
        </div>
    );
}

/**
 * `components` entries for InkstreamMarkdown that make `<Tabs>` switchable.
 * The tag names are not part of react-markdown's Components type.
 */
export const markdownTabComponents = {
    tabs: MarkdownTabs,
    tab: MarkdownTab,
} as unknown as Components;
