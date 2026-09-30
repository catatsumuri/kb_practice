import { usePage } from '@inertiajs/react';
import AppLayoutTemplate from '@/layouts/app/app-header-layout';
import GuestLayout from '@/layouts/guest-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    wide,
    documentReader = false,
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    wide?: boolean;
    documentReader?: boolean;
    children: React.ReactNode;
}) {
    const { auth } = usePage().props;

    // Guests only ever reach AppLayout-wrapped pages through a public
    // document or namespace (the only routes that don't require login), so
    // give them the lightweight guest chrome instead of the authenticated
    // sidebar/header shell.
    if (!auth.user) {
        return (
            <GuestLayout
                breadcrumbs={breadcrumbs}
                wide={wide}
                documentReader={documentReader}
            >
                {children}
            </GuestLayout>
        );
    }

    return (
        <AppLayoutTemplate
            breadcrumbs={breadcrumbs}
            documentReader={documentReader}
        >
            {children}
        </AppLayoutTemplate>
    );
}
