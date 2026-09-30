import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import type { AppLayoutProps } from '@/types';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
    documentReader = false,
}: AppLayoutProps) {
    return (
        <AppShell variant="header" documentReader={documentReader}>
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent
                variant="header"
                className={
                    documentReader ? 'lg:min-h-0 lg:overflow-hidden' : undefined
                }
            >
                {children}
            </AppContent>
        </AppShell>
    );
}
