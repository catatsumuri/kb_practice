import { Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { home } from '@/routes';
import type { GuestLayoutProps } from '@/types';

export default function GuestLayout({ children }: GuestLayoutProps) {
    return (
        <div className="mx-auto grid max-w-3xl gap-4 p-4">
            <header className="flex items-center text-sm">
                <Link href={home()} className="flex items-center">
                    <AppLogo />
                </Link>
            </header>

            {children}
        </div>
    );
}
