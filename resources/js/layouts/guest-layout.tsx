import { Link, usePage } from '@inertiajs/react';
import { lang } from '@erag/lang-sync-inertia/react';
import AppLogo from '@/components/app-logo';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { home, login } from '@/routes';
import type { GuestLayoutProps } from '@/types';

export default function GuestLayout({
    breadcrumbs = [],
    wide = false,
    children,
}: GuestLayoutProps) {
    const { __ } = lang();
    const { url } = usePage();

    return (
        <div
            className={cn(
                'mx-auto grid min-w-0 gap-4 p-4',
                wide ? 'max-w-7xl' : 'max-w-3xl',
            )}
        >
            <header className="flex items-center justify-between text-sm">
                <Link href={home()} className="flex items-center">
                    <AppLogo />
                </Link>
                <Button asChild variant="outline" size="sm">
                    <Link href={login({ query: { redirect: url } })}>
                        {__('Log in')}
                    </Link>
                </Button>
            </header>

            <Breadcrumbs breadcrumbs={breadcrumbs} />

            {children}
        </div>
    );
}
