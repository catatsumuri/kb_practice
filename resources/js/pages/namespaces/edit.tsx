import { Head, setLayoutProps } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/DocumentController';
import {
    edit,
    show,
    update,
} from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { NamespaceForm } from '@/components/namespace-form';

import type { DocumentNamespace } from '@/types';

type EditNamespaceProps = {
    namespace: Pick<
        DocumentNamespace,
        'slug' | 'name' | 'source_url' | 'is_public'
    >;
};

export default function EditNamespace({ namespace }: EditNamespaceProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'ドキュメント', href: index() },
            { title: namespace.name, href: show(namespace.slug) },
            { title: '編集', href: edit(namespace.slug) },
        ],
    });

    return (
        <>
            <Head title={`「${namespace.name}」を編集`} />

            <main className="p-4">
                <NamespaceForm
                    title={`「${namespace.name}」を編集`}
                    description="表示名・元サイトURL・公開範囲を編集できます。"
                    form={update.form(namespace.slug)}
                    cancelHref={show(namespace.slug)}
                    submitLabel="更新"
                    defaultValues={namespace}
                />
            </main>
        </>
    );
}
