import type { ReactNode } from 'react';
import { CardDescription } from '@/components/ui/card';
import { cn, formatDate } from '@/lib/utils';

type DocumentMetaProps = {
    author: string;
    createdAt: string;
    className?: string;
    children?: ReactNode;
};

export function DocumentMeta({
    author,
    createdAt,
    className,
    children,
}: DocumentMetaProps) {
    return (
        <CardDescription
            className={cn('flex flex-wrap gap-x-3 gap-y-1', className)}
        >
            <span>作成者：{author}</span>
            <time dateTime={createdAt}>{formatDate(createdAt)}</time>
            {children}
        </CardDescription>
    );
}
