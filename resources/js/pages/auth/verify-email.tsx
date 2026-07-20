import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { lang } from '@erag/lang-sync-inertia/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { __ } = lang();

    setLayoutProps({
        title: __('Email verification'),
        description: __(
            'Please verify your email address by clicking on the link we just emailed to you.',
        ),
    });

    return (
        <>
            <Head title={__('Email verification')} />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {__(
                        'A new verification link has been sent to the email address you provided during registration.',
                    )}
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            {__('Resend verification email')}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            {__('Log out')}
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}
