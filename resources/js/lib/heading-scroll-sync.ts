export type HeadingAnchor = {
    id: string;
    offset: number;
};

/**
 * Every h1-h4's id and content-relative offset (distance from the top of
 * the scrollable content, independent of the current scroll position)
 * inside a container, in document order. h5/h6 are excluded because
 * InkstreamMarkdown doesn't assign them ids (see
 * @catatsumuri/inkstream/react's headingComponents), so they could never
 * match across panes anyway.
 */
export function getHeadingAnchors(container: HTMLElement): HeadingAnchor[] {
    const containerTop = container.getBoundingClientRect().top;

    return Array.from(
        container.querySelectorAll<HTMLElement>('h1, h2, h3, h4'),
    )
        .map((heading) => ({
            id: heading.id,
            offset:
                heading.getBoundingClientRect().top -
                containerTop +
                container.scrollTop,
        }))
        .filter((heading) => heading.id !== '');
}

type ComputeSyncedScrollTopParams = {
    sourceScrollTop: number;
    sourceScrollHeight: number;
    sourceHeadingAnchors: HeadingAnchor[];
    targetScrollHeight: number;
    targetHeadingAnchors: HeadingAnchor[];
};

/**
 * Maps a scroll position in the source pane to the equivalent position in
 * the target pane, keeping aligned only the headings whose id matches on
 * both sides — a heading's id is its slugified text, so a translation and
 * its original only share an id where the heading text itself is the same
 * on both sides (an untranslated proper noun, a code identifier, or a
 * heading an author deliberately mirrors word-for-word). Matching by id
 * rather than by position means an unmatched heading (the common case,
 * since translated and original headings normally slugify differently)
 * is simply not an anchor, instead of two unrelated headings being forced
 * to correspond just because they happen to share an index.
 *
 * The matched anchors divide each pane into segments (before the first
 * anchor, between each pair, and after the last); the source's position
 * within its current segment (as a 0-1 fraction) is applied to the same
 * segment in the target. With no matching ids at all, this is a single
 * segment spanning the whole pane — a plain proportional scroll sync,
 * rather than breaking.
 */
export function computeSyncedScrollTop({
    sourceScrollTop,
    sourceScrollHeight,
    sourceHeadingAnchors,
    targetScrollHeight,
    targetHeadingAnchors,
}: ComputeSyncedScrollTopParams): number {
    const targetIndexById = new Map<string, number>();

    targetHeadingAnchors.forEach((heading, index) => {
        if (!targetIndexById.has(heading.id)) {
            targetIndexById.set(heading.id, index);
        }
    });

    const sourceOffsets: number[] = [];
    const targetOffsets: number[] = [];
    let lastTargetIndex = -1;

    for (const heading of sourceHeadingAnchors) {
        const targetIndex = targetIndexById.get(heading.id);

        // Skip ids unique to the source, and ids that matched a target
        // heading earlier than the last accepted anchor — the anchors
        // must appear in the same relative order on both sides.
        if (targetIndex === undefined || targetIndex <= lastTargetIndex) {
            continue;
        }

        sourceOffsets.push(heading.offset);
        targetOffsets.push(targetHeadingAnchors[targetIndex].offset);
        lastTargetIndex = targetIndex;
    }

    const sourceBounds = [0, ...sourceOffsets, sourceScrollHeight];
    const targetBounds = [0, ...targetOffsets, targetScrollHeight];

    let segment = sourceBounds.length - 2;

    for (let i = 0; i < sourceBounds.length - 1; i++) {
        if (sourceScrollTop < sourceBounds[i + 1]) {
            segment = i;
            break;
        }
    }

    const sourceSegmentStart = sourceBounds[segment];
    const sourceSegmentEnd = sourceBounds[segment + 1];
    const fraction =
        sourceSegmentEnd > sourceSegmentStart
            ? (sourceScrollTop - sourceSegmentStart) /
              (sourceSegmentEnd - sourceSegmentStart)
            : 0;
    const clampedFraction = Math.min(1, Math.max(0, fraction));

    const targetSegmentStart = targetBounds[segment];
    const targetSegmentEnd = targetBounds[segment + 1];

    return (
        targetSegmentStart +
        clampedFraction * (targetSegmentEnd - targetSegmentStart)
    );
}
