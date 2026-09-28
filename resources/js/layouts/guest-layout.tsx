import { Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import type { GuestLayoutProps } from '@/types';

export default function GuestLayout({
    breadcrumbs = [],
    wide = false,
    children,
}: GuestLayoutProps) {
    return (
        <div
            className={cn(
                'mx-auto grid min-w-0 gap-4 p-4',
                wide ? 'max-w-7xl' : 'max-w-3xl',
            )}
        >
            <header className="flex items-center text-sm">
                <Link href={home()} className="flex items-center">
                    <AppLogo />
                </Link>
            </header>

            <Breadcrumbs breadcrumbs={breadcrumbs} />

            {children}
        </div>
    );
}
