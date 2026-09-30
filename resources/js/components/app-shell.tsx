import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
    documentReader?: boolean;
};

export function AppShell({
    children,
    variant = 'sidebar',
    documentReader = false,
}: Props) {
    const isOpen = usePage().props.sidebarOpen;

    if (variant === 'header') {
        return (
            <div
                className={cn(
                    'flex min-h-screen w-full flex-col',
                    documentReader && 'lg:h-dvh lg:min-h-0 lg:overflow-hidden',
                )}
            >
                {children}
            </div>
        );
    }

    return <SidebarProvider defaultOpen={isOpen}>{children}</SidebarProvider>;
}
