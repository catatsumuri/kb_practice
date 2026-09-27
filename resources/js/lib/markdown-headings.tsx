import { slugify } from '@catatsumuri/inkstream';
import { Children, isValidElement } from 'react';
import type { ReactNode } from 'react';
import type { Components } from 'react-markdown';

/**
 * Collects the plain text of a rendered heading's children, so the slug
 * matches what extractMarkdownHeadings computes from the markdown source.
 */
function extractHeadingText(node: ReactNode): string {
    if (typeof node === 'string' || typeof node === 'number') {
        return String(node);
    }

    if (!node || typeof node === 'boolean') {
        return '';
    }

    if (Array.isArray(node)) {
        return node.map(extractHeadingText).join('');
    }

    if (!isValidElement<{ children?: ReactNode }>(node)) {
        return '';
    }

    return Children.toArray(node.props.children)
        .map(extractHeadingText)
        .join('');
}

function makeHeadingRenderer(level: 1 | 2 | 3 | 4) {
    return function Heading({ children }: { children?: ReactNode }) {
        const Tag = `h${level}` as 'h1' | 'h2' | 'h3' | 'h4';

        return <Tag id={slugify(extractHeadingText(children))}>{children}</Tag>;
    };
}

/**
 * react-markdown renderers for h1-h4 that add a slug-derived id to each
 * heading, matching `extractMarkdownHeadings` so a table of contents built
 * from the raw markdown links to the right element.
 */
export const headingComponents: Pick<Components, 'h1' | 'h2' | 'h3' | 'h4'> = {
    h1: makeHeadingRenderer(1),
    h2: makeHeadingRenderer(2),
    h3: makeHeadingRenderer(3),
    h4: makeHeadingRenderer(4),
};
