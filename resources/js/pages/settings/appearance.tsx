import { Head, setLayoutProps } from '@inertiajs/react';
import { lang } from '@erag/lang-sync-inertia/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { __ } = lang();

    setLayoutProps({
        breadcrumbs: [
            {
                title: __('Appearance settings'),
                href: editAppearance(),
            },
        ],
    });

    return (
        <>
            <Head title={__('Appearance settings')} />

            <h1 className="sr-only">{__('Appearance settings')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={__('Appearance settings')}
                    description={__(
                        'Update the appearance settings for your account',
                    )}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}
