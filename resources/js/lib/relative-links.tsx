import { inkstreamDefaultComponents } from '@catatsumuri/inkstream/react';
import type { ComponentType } from 'react';
import type { Components } from 'react-markdown';
import { cn } from '@/lib/utils';

/**
 * True for a root-relative path like "/concepts/system-one" — as opposed
 * to a protocol-relative "//host/path", a same-page "#anchor", or an
 * already-absolute URL — which is the shape links keep when markdown is
 * copied verbatim from another site's docs.
 */
function isRootRelative(href: string): boolean {
    return href.startsWith('/') && !href.startsWith('//');
}

/**
 * A resolver's return value: either a plain URL (always treated as
 * resolved) or an object flagging whether the path matched a real
 * document, so the unresolved case can be styled as a "red link" —
 * mirrors inkstream's own WikilinkResolution contract.
 */
export type RelativeLinkResolution = string | { url: string; exists?: boolean };

/**
 * Builds an `a` renderer for InkstreamMarkdown's `components` prop that
 * rewrites root-relative hrefs through `resolve` (passed the path with its
 * leading slash stripped), leaving every other href untouched. Root-
 * relative links only ever resolved against the site the markdown was
 * copied from, never against this app's own origin, so left alone they
 * 404 once rendered here.
 */
export function createRelativeLinkComponent(
    resolve: (path: string) => RelativeLinkResolution,
): NonNullable<Components['a']> {
    return function RelativeLink({ href, children, className, ...rest }) {
        if (!href || !isRootRelative(href)) {
            return (
                <a href={href} className={className} {...rest}>
                    {children}
                </a>
            );
        }

        const resolution = resolve(href.slice(1));
        const resolvedHref =
            typeof resolution === 'string' ? resolution : resolution.url;
        const exists =
            typeof resolution === 'string' ? true : (resolution.exists ?? true);

        return (
            <a
                href={resolvedHref}
                className={cn(className, !exists && 'ink-wikilink-broken')}
                {...rest}
            >
                {children}
            </a>
        );
    };
}

type CardProps = { href?: string } & Record<string, unknown>;

/**
 * The renderers InkstreamMarkdown needs to keep root-relative links working:
 * `a` for ordinary markdown links, and `card` because a `<Card href>` is
 * rendered by inkstream as its own anchor rather than through `a`, so it
 * would otherwise keep pointing at the site the markdown was copied from.
 */
export function createRelativeLinkComponents(
    resolve: (path: string) => RelativeLinkResolution,
): Components {
    const DefaultCard = (
        inkstreamDefaultComponents as unknown as Record<
            string,
            ComponentType<CardProps>
        >
    ).card;

    function RelativeCard(props: CardProps) {
        const { href } = props;

        if (typeof href !== 'string' || !isRootRelative(href)) {
            return <DefaultCard {...props} />;
        }

        const resolution = resolve(href.slice(1));

        return (
            <DefaultCard
                {...props}
                href={
                    typeof resolution === 'string' ? resolution : resolution.url
                }
            />
        );
    }

    return {
        a: createRelativeLinkComponent(resolve),
        card: RelativeCard,
    } as Components;
}
