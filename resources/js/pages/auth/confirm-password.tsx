import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { lang } from '@erag/lang-sync-inertia/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
/* @chisel-passkeys */
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/passkey-verify';
/* @end-chisel-passkeys */

export default function ConfirmPassword() {
    const { __ } = lang();

    setLayoutProps({
        title: __('Confirm password'),
        description: __(
            'This is a secure area of the application. Please confirm your password before continuing.',
        ),
    });

    return (
        <>
            <Head title={__('Confirm password')} />

            {/* @chisel-passkeys */}
            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label={__('Confirm with passkey')}
                loadingLabel={__('Confirming...')}
                separator={__('Or confirm with password')}
            />
            {/* @end-chisel-passkeys */}

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">{__('Password')}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder={__('Password')}
                                autoComplete="current-password"
                                autoFocus
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center">
                            <Button
                                className="w-full"
                                disabled={processing}
                                data-test="confirm-password-button"
                            >
                                {processing && <Spinner />}
                                {__('Confirm password')}
                            </Button>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}
