import { useEffect, useRef } from 'react';
import type { RefObject } from 'react';
import {
    computeSyncedScrollTop,
    getHeadingAnchors,
} from '@/lib/heading-scroll-sync';
import type { HeadingAnchor } from '@/lib/heading-scroll-sync';

type UseSyncedScrollOptions = {
    enabled: boolean;
};

/**
 * Keeps two scrollable panes' matching headings aligned as either one is
 * scrolled, so a translation and its (differently-lengthed) original stay
 * readable side by side. Only headings whose id matches on both sides
 * (see computeSyncedScrollTop) are used as anchors, degrading to a single
 * whole-pane proportional sync when none match instead of breaking.
 */
export function useSyncedScroll(
    leftRef: RefObject<HTMLElement | null>,
    rightRef: RefObject<HTMLElement | null>,
    { enabled }: UseSyncedScrollOptions,
) {
    const leftAnchorsRef = useRef<HeadingAnchor[]>([]);
    const rightAnchorsRef = useRef<HeadingAnchor[]>([]);

    // The scrollTop we ourselves last wrote to each pane (after the
    // browser's own clamping near the top/bottom of content), so the
    // `scroll` event that write provokes can be told apart from a
    // genuine user-driven scroll on that same pane and ignored — without
    // this, syncing left from right would echo back and re-sync right
    // from left's (possibly clamped, therefore different) position,
    // fighting over the "true" position every time either pane clamps.
    const lastSyncedLeftRef = useRef<number | null>(null);
    const lastSyncedRightRef = useRef<number | null>(null);

    useEffect(() => {
        const left = leftRef.current;
        const right = rightRef.current;

        if (!enabled || !left || !right) {
            return;
        }

        const recalculate = () => {
            leftAnchorsRef.current = getHeadingAnchors(left);
            rightAnchorsRef.current = getHeadingAnchors(right);
        };

        recalculate();

        const sync = (
            source: HTMLElement,
            target: HTMLElement,
            sourceAnchors: HeadingAnchor[],
            targetAnchors: HeadingAnchor[],
            targetLastSyncedRef: RefObject<number | null>,
        ) => {
            target.scrollTop = computeSyncedScrollTop({
                sourceScrollTop: source.scrollTop,
                sourceScrollHeight: source.scrollHeight,
                sourceHeadingAnchors: sourceAnchors,
                targetScrollHeight: target.scrollHeight,
                targetHeadingAnchors: targetAnchors,
            });
            // Record what actually landed (the browser may have clamped
            // the requested value), not what was requested.
            targetLastSyncedRef.current = target.scrollTop;
        };

        const onLeftScroll = () => {
            if (lastSyncedLeftRef.current === left.scrollTop) {
                lastSyncedLeftRef.current = null;

                return;
            }

            sync(
                left,
                right,
                leftAnchorsRef.current,
                rightAnchorsRef.current,
                lastSyncedRightRef,
            );
        };

        const onRightScroll = () => {
            if (lastSyncedRightRef.current === right.scrollTop) {
                lastSyncedRightRef.current = null;

                return;
            }

            sync(
                right,
                left,
                rightAnchorsRef.current,
                leftAnchorsRef.current,
                lastSyncedLeftRef,
            );
        };

        left.addEventListener('scroll', onLeftScroll);
        right.addEventListener('scroll', onRightScroll);

        // Headings can shift after mount (mermaid diagrams and images
        // resolve their height asynchronously), so re-measure whenever
        // either pane's rendered markdown content changes size.
        const leftContent =
            left.querySelector<HTMLElement>('.ink-markdown') ?? left;
        const rightContent =
            right.querySelector<HTMLElement>('.ink-markdown') ?? right;
        const resizeObserver = new ResizeObserver(recalculate);
        resizeObserver.observe(leftContent);
        resizeObserver.observe(rightContent);
        window.addEventListener('resize', recalculate);

        return () => {
            left.removeEventListener('scroll', onLeftScroll);
            right.removeEventListener('scroll', onRightScroll);
            resizeObserver.disconnect();
            window.removeEventListener('resize', recalculate);
        };
    }, [enabled, leftRef, rightRef]);
}
