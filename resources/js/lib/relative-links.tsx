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
